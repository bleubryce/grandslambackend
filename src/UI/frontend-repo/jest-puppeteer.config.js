module.exports = {
  launch: {
    headless: process.env.HEADLESS !== 'false',
    defaultViewport: {
      width: 1280,
      height: 720
    },
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--disable-gpu'
    ]
  },
  server: {
    command: 'npm start',
    port: 3000,
    launchTimeout: 30000,
    debug: true
  }
}; 