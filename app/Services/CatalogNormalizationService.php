<?php

namespace App\Services;

use App\Models\CategoriaInsumo;
use App\Models\UnidadMedida;
use Illuminate\Support\Str;

class CatalogNormalizationService
{
    /**
     * Mapeo de sinónimos para categorías
     */
    private const CATEGORY_SYNONYMS = [
        'textiles' => ['telas', 'tela', 'material textil', 'materiales textiles'],
        'estructuras' => ['estructura', 'varillas', 'varilla', 'tubos', 'tubo', 'marco', 'marcos'],
        'herrajes' => ['hardware', 'herraje', 'accesorios metálicos', 'metales'],
        'accesorios' => ['accesorio', 'complementos', 'complemento'],
        'cordaje' => ['cuerdas', 'cuerda', 'sogas', 'soga', 'cables', 'cable'],
    ];

    /**
     * Mapeo de sinónimos para unidades
     */
    private const UNIT_SYNONYMS = [
        'm' => ['metros', 'metro', 'mt', 'mts'],
        'cm' => ['centimetros', 'centímetro', 'centímetros', 'cmt'],
        'mm' => ['milimetros', 'milímetro', 'milímetros'],
        'kg' => ['kilogramo', 'kilogramos', 'kgs'],
        'g' => ['gramos', 'gramo'],
        'l' => ['litro', 'litros', 'lt', 'lts'],
        'ml' => ['mililitro', 'mililitros'],
        'pz' => ['piezas', 'pieza', 'unidades', 'unidad', 'u', 'ud', 'uds'],
        'dz' => ['docena', 'docenas'],
        'm²' => ['metro cuadrado', 'metros cuadrados', 'm2', 'metro2'],
        'm³' => ['metro cúbico', 'metros cúbicos', 'm3', 'metro3'],
        'rl' => ['rollo', 'rollos'],
    ];

    /**
     * Busca una categoría por texto, intentando normalizar
     *
     * @param string $searchText
     * @return CategoriaInsumo|null
     */
    public function normalizarCategoria(string $searchText): ?CategoriaInsumo
    {
        $normalized = $this->normalizeText($searchText);

        // 1. Búsqueda exacta normalizada
        $categoria = CategoriaInsumo::where('nombre', $normalized)->first();
        if ($categoria) {
            return $categoria;
        }

        // 2. Búsqueda por slug parcial
        $slug = Str::slug($normalized);
        $categoria = CategoriaInsumo::where('slug', 'like', '%' . $slug . '%')->first();
        if ($categoria) {
            return $categoria;
        }

        // 3. Búsqueda por coincidencia LIKE
        $categoria = CategoriaInsumo::where('nombre', 'like', '%' . $normalized . '%')->first();
        if ($categoria) {
            return $categoria;
        }

        // 4. Búsqueda por sinónimos
        foreach (self::CATEGORY_SYNONYMS as $mainName => $synonyms) {
            foreach ($synonyms as $synonym) {
                if ($this->textSimilarity($normalized, $synonym) > 0.8) {
                    $categoria = CategoriaInsumo::where('nombre', 'like', '%' . $mainName . '%')->first();
                    if ($categoria) {
                        return $categoria;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Busca una unidad de medida por texto, intentando normalizar
     *
     * @param string $searchText
     * @return UnidadMedida|null
     */
    public function normalizarUnidad(string $searchText): ?UnidadMedida
    {
        $normalized = $this->normalizeText($searchText);

        // 1. Búsqueda exacta por nombre
        $unidad = UnidadMedida::where('nombre', $normalized)->first();
        if ($unidad) {
            return $unidad;
        }

        // 2. Búsqueda exacta por abreviatura
        $unidad = UnidadMedida::where('abreviatura', $normalized)->first();
        if ($unidad) {
            return $unidad;
        }

        // 3. Búsqueda por sinónimos
        foreach (self::UNIT_SYNONYMS as $mainUnit => $synonyms) {
            if ($this->textSimilarity($normalized, $mainUnit) > 0.8) {
                $unidad = UnidadMedida::where('abreviatura', $mainUnit)->first();
                if ($unidad) {
                    return $unidad;
                }
            }

            foreach ($synonyms as $synonym) {
                if ($this->textSimilarity($normalized, $synonym) > 0.8) {
                    $unidad = UnidadMedida::where('abreviatura', $mainUnit)->first();
                    if ($unidad) {
                        return $unidad;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Crea una nueva categoría si no existe (o retorna existente)
     *
     * @param string $nombre
     * @return CategoriaInsumo
     */
    public function crearOEncontrarCategoria(string $nombre): CategoriaInsumo
    {
        // Intenta normalizar primero
        $existente = $this->normalizarCategoria($nombre);
        if ($existente) {
            return $existente;
        }

        // Crea nueva
        return CategoriaInsumo::create([
            'nombre' => $this->normalizeText($nombre),
            'slug' => Str::slug($nombre),
            'descripcion' => "Categoría creada automáticamente desde entrada: $nombre",
            'activo' => true,
        ]);
    }

    /**
     * Crea una nueva unidad de medida si no existe (o retorna existente)
     *
     * @param string $nombre
     * @param string|null $abreviatura
     * @return UnidadMedida
     */
    public function crearOEncontrarUnidad(string $nombre, ?string $abreviatura = null): UnidadMedida
    {
        // Intenta normalizar primero
        $existente = $this->normalizarUnidad($nombre);
        if ($existente) {
            return $existente;
        }

        // Crea nueva con abreviatura
        $abbr = $abreviatura ?? substr($this->normalizeText($nombre), 0, 2);

        return UnidadMedida::create([
            'nombre' => $this->normalizeText($nombre),
            'abreviatura' => strtolower($abbr),
            'tipo' => 'custom',
            'factor_conversion_base' => 1.0,
            'activo' => true,
        ]);
    }

    /**
     * Normaliza texto: trim, lowercase, acentos
     *
     * @param string $text
     * @return string
     */
    private function normalizeText(string $text): string
    {
        $text = trim($text);
        $text = strtolower($text);
        // Remover acentos
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        return $text;
    }

    /**
     * Calcula similitud entre dos strings (0-1)
     * usando algoritmo de Levenshtein
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    private function textSimilarity(string $str1, string $str2): float
    {
        $len = max(strlen($str1), strlen($str2));
        if ($len === 0) {
            return 1.0;
        }
        return 1 - (levenshtein($str1, $str2) / $len);
    }

    /**
     * Busca categorías que coincidan con el texto (para autocompletado)
     *
     * @param string $searchText
     * @param int $limit
     * @return array
     */
    public function buscarCategorias(string $searchText, int $limit = 10): array
    {
        $normalized = $this->normalizeText($searchText);

        return CategoriaInsumo::where('activo', true)
            ->where(function ($query) use ($normalized, $searchText) {
                $query->where('nombre', 'like', '%' . $normalized . '%')
                    ->orWhere('slug', 'like', '%' . Str::slug($searchText) . '%');
            })
            ->orderBy('nombre')
            ->limit($limit)
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                    'descripcion' => $cat->descripcion,
                ];
            })
            ->toArray();
    }

    /**
     * Busca unidades de medida que coincidan con el texto (para autocompletado)
     *
     * @param string $searchText
     * @param int $limit
     * @return array
     */
    public function buscarUnidades(string $searchText, int $limit = 10): array
    {
        $normalized = $this->normalizeText($searchText);

        return UnidadMedida::where('activo', true)
            ->where(function ($query) use ($normalized, $searchText) {
                $query->where('nombre', 'like', '%' . $normalized . '%')
                    ->orWhere('abreviatura', 'like', '%' . strtolower($searchText) . '%');
            })
            ->orderBy('nombre')
            ->limit($limit)
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'nombre' => $unit->nombre,
                    'abreviatura' => $unit->abreviatura,
                    'nombre_completo' => "{$unit->nombre} ({$unit->abreviatura})",
                ];
            })
            ->toArray();
    }
}
