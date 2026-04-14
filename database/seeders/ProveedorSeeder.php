<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $proveedores = [
            [
                'razon_social' => 'Textiles y Manufactura S.A. de C.V.',
                'nombre_comercial' => 'TMT Textiles',
                'rfc' => 'TMT800101ABC',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Av. Industrial 123, Zona Industrial',
                'ciudad' => 'Monterrey',
                'estado' => 'Nuevo León',
                'codigo_postal' => '64010',
                'pais' => 'México',
                'telefono_principal' => '+52-81-1234-5678',
                'email_general' => 'ventas@tmtextiles.com.mx',
                'dias_credito' => 30,
                'limite_credito' => 100000.0,
                'descuento_porcentaje' => 5.0,
                'calificacion' => 4.8,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, ISO 14001',
                'notas' => 'Proveedor confiable con entregas puntuales',
            ],
            [
                'razon_social' => 'Estructuras Metálicas del Noreste S.A.',
                'nombre_comercial' => 'Metales NE',
                'rfc' => 'MEN850612XYZ',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Calle Principal 456',
                'ciudad' => 'Guadalajara',
                'estado' => 'Jalisco',
                'codigo_postal' => '44100',
                'pais' => 'México',
                'telefono_principal' => '+52-33-8765-4321',
                'email_general' => 'pedidos@metalesne.com.mx',
                'dias_credito' => 15,
                'limite_credito' => 80000.0,
                'descuento_porcentaje' => 3.5,
                'calificacion' => 4.5,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001',
                'notas' => 'Especialista en tubería de aluminio',
            ],
            [
                'razon_social' => 'Herrajes y Accesorios Importados S.A. de C.V.',
                'nombre_comercial' => 'Herrajes Plus',
                'rfc' => 'HPI920315DEF',
                'tipo_proveedor' => 'Herrajes',
                'direccion' => 'Paseo del Comercio 789',
                'ciudad' => 'Ciudad de México',
                'estado' => 'CDMX',
                'codigo_postal' => '06500',
                'pais' => 'México',
                'telefono_principal' => '+52-55-5555-1234',
                'email_general' => 'info@herrajesplus.com.mx',
                'dias_credito' => 20,
                'limite_credito' => 50000.0,
                'descuento_porcentaje' => 4.0,
                'calificacion' => 4.6,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001',
                'notas' => 'Importador de hebillas y sistemas de cierre',
            ],
            [
                'razon_social' => 'Materiales de Aislamiento Innovadores S.A.',
                'nombre_comercial' => 'MaAi Espumas',
                'rfc' => 'MAI880227GHI',
                'tipo_proveedor' => 'Materia Prima',
                'direccion' => 'Blvd. Tecnológico 321',
                'ciudad' => 'Querétaro',
                'estado' => 'Querétaro',
                'codigo_postal' => '76100',
                'pais' => 'México',
                'telefono_principal' => '+52-42-2000-3000',
                'email_general' => 'ventas@maiespumas.com.mx',
                'dias_credito' => 25,
                'limite_credito' => 65000.0,
                'descuento_porcentaje' => 6.0,
                'calificacion' => 4.7,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, RoHS',
                'notas' => 'Especialista en espumas técnicas y aislamiento',
            ],
            [
                'razon_social' => 'Empaques Ecológicos Sustentables S.A. de C.V.',
                'nombre_comercial' => 'EcoPackage',
                'rfc' => 'EPS900525JKL',
                'tipo_proveedor' => 'Empaque',
                'direccion' => 'Km 15 Carretera a Toluca',
                'ciudad' => 'Toluca',
                'estado' => 'Estado de México',
                'codigo_postal' => '50080',
                'pais' => 'México',
                'telefono_principal' => '+52-72-2650-1500',
                'email_general' => 'cotizaciones@ecopackage.mx',
                'dias_credito' => 35,
                'limite_credito' => 120000.0,
                'descuento_porcentaje' => 7.5,
                'calificacion' => 4.9,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, FMCG Certified',
                'notas' => 'Proveedores de cajas corrugadas y empaque biodegradable',
            ],
            [
                'razon_social' => 'Química Avanzada Para Textiles S.A.',
                'nombre_comercial' => 'QuimAv Textil',
                'rfc' => 'QAT750828MNO',
                'tipo_proveedor' => 'Químicos',
                'direccion' => 'Privada de la Industria 555',
                'ciudad' => 'Puebla',
                'estado' => 'Puebla',
                'codigo_postal' => '72000',
                'pais' => 'México',
                'telefono_principal' => '+52-22-2300-8000',
                'email_general' => 'ordenes@quimavtextil.com',
                'dias_credito' => 10,
                'limite_credito' => 40000.0,
                'descuento_porcentaje' => 2.5,
                'calificacion' => 4.3,
                'estatus' => 'Activo',
                'certificaciones' => 'ISO 9001, HAZMAT Licensed',
                'notas' => 'Adhesivos, selladores y tratamientos especiales para textiles',
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::updateOrCreate(
                ['razon_social' => $proveedor['razon_social']],
                $proveedor
            );
        }
    }
}
