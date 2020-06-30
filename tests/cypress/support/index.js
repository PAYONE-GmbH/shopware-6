import 'cypress-pipe'
import './commands'

// use `Cypress` instead of `cy` so this persists across all tests
Cypress.on('window:before:load', win => {
    win.fetch = null
})
cy.on('uncaught:exception', (err, runnable) => {
    return false
})
module.exports = (on, config) => {
    on('before:browser:launch', (browser = {}, args) => {
        if (browser.name === 'chrome') {
            args.push('--disable-site-isolation-trials')
            return args
        }
    })
}
