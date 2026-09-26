module.exports = {
  testDir: './tests/browser',
  use: {
    baseURL: process.env.BASE_URL || 'http://app:8080',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure'
  },
  reporter: [['html', { outputFolder: 'build/playwright' }]]
};
