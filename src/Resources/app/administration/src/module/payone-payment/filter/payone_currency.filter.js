const { Filter } = Shopware;

Filter.register('payone_currency', (value, format, decimalPlaces) => {
    if (value === null) {
        return '-';
    }

    value /= 100;

    return Filter.getByName('currency')(value, format, decimalPlaces);
});
