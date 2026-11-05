import archiver from 'archiver';
import { createWriteStream } from 'node:fs';
import { mkdir, readFile, stat } from 'node:fs/promises';
import path from 'node:path';
import { root } from './workspaces.mjs';

const directory = process.cwd();
const manifest = JSON.parse(await readFile(path.join(directory, 'package.json'), 'utf8'));
const slug = path.basename(directory);
const destination = path.join(root, 'dist', `${slug}-${manifest.version}.zip`);
await mkdir(path.dirname(destination), { recursive: true });

// Validate all release inputs before opening the archive.
const entries = await Promise.all(manifest.files.map(async (entry) => ({
  entry,
  isDirectory: (await stat(path.join(directory, entry))).isDirectory(),
})));

const output = createWriteStream(destination);
const archive = archiver('zip', { zlib: { level: 9 } });
const finished = new Promise((resolve, reject) => {
  output.on('close', resolve);
  output.on('error', reject);
  archive.on('error', reject);
  archive.on('warning', reject);
});
archive.pipe(output);
archive.file(path.join(root, 'LICENSE'), { name: `${slug}/LICENSE` });
for (const { entry, isDirectory } of entries) {
  archive.glob(isDirectory ? `${entry}/**/*` : entry, {
    cwd: directory,
    ignore: ['**/*.map'],
  }, { prefix: slug });
}
await archive.finalize();
await finished;
console.log(`Created ${destination}`);
