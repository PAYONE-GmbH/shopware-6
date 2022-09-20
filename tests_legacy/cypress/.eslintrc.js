module.exports = {
    'extends': 'standard',
    'root': true,
    'globals': {
        'Cypress': true,
        'cy': true
    },
    'rules': {
        'indent': [
            'error',
            4,
            {'SwitchCase': 1}
        ],
    }
};
