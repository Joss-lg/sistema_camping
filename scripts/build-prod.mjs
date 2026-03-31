import { existsSync, rmSync } from 'node:fs';
import { resolve } from 'node:path';
import { build } from 'vite';

const hotFilePath = resolve(process.cwd(), 'public', 'hot');

if (existsSync(hotFilePath)) {
    rmSync(hotFilePath, { force: true });
    console.log('[build:prod] Removed stale public/hot file.');
} else {
    console.log('[build:prod] No public/hot file found.');
}

try {
    await build();
    console.log('[build:prod] Vite build finished successfully.');
} catch (error) {
    console.error('[build:prod] Failed to run Vite build.');
    console.error(error);
    process.exit(1);
}
