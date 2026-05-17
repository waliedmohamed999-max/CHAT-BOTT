import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: false,
  build: {
    outDir: 'public/assets/.vite',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: {
        app: 'public/assets/app.js',
        liveChatWidget: 'public/assets/live-chat-widget.js'
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name][extname]'
      }
    }
  }
});
