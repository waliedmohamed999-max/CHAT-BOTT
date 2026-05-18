import { copyFileSync, existsSync, mkdirSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const sourceAssets = join(root, 'public', 'assets');
const dist = join(root, 'dist');
const distAssets = join(dist, 'assets');

function copyDirectory(source, destination) {
  mkdirSync(destination, { recursive: true });

  for (const entry of readdirSync(source)) {
    if (entry === '.vite') {
      continue;
    }

    const sourcePath = join(source, entry);
    const destinationPath = join(destination, entry);

    if (statSync(sourcePath).isDirectory()) {
      copyDirectory(sourcePath, destinationPath);
      continue;
    }

    copyFileSync(sourcePath, destinationPath);
  }
}

rmSync(dist, { recursive: true, force: true });
mkdirSync(distAssets, { recursive: true });

if (existsSync(sourceAssets)) {
  copyDirectory(sourceAssets, distAssets);
}

writeFileSync(
  join(dist, '.vercel-build'),
  'Static assets only. PHP requests are routed to /api/index.php.\n',
);
