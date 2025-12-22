export default {
  // For the sake of using relative urls in page.goto('module/...') the trailing slash is needed
  baseUrl: process.env.PLAYWRIGHT_BASE_URL || 'https://local.v13.in2publish.de/typo3/',
  login: {
    admin: {
      username: process.env.TYPO3_BACKEND_ADMIN_USERNAME || 'admin',
      password: process.env.TYPO3_BACKEND_ADMIN_PASSWORD || 'password'
    }
  }
};
