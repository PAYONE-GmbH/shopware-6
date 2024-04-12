const global = {
    allowedPaymentMethods: [],
    allowedCountries: {},
    allowedCurrencies: {},
};

module.exports = {
    addAllowedPaymentMethod(id) {
        global.allowedPaymentMethods.push(id);

        return id;
    },
    addAllowedCurrency({isoCode, id}) {
        global.allowedCurrencies[isoCode] = id;

        return null;
    },
    addAllowedCountry({isoCode, id}) {
        global.allowedCountries[isoCode] = id;

        return null;
    },
    isPaymentMethodAllowed: id => global.allowedPaymentMethods.includes(id),
    getAllowedCurrencyId: isoCode => global.allowedCurrencies[isoCode] ?? null,
    getAllowedCountryId: isoCode => global.allowedCountries[isoCode] ?? null,
    addToGlobalStore({key, value}) {
        global[key] = value;

        return value;
    },
    getFromGlobalStore: key => global[key] ?? null
};
