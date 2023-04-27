const { currency } = Shopware.Utils.format;

export default (value, format, decimalPrecision, decimalPlaces) => {
    if (value === null) {
        return '-';
    }

    if (!decimalPrecision) {
        decimalPrecision = 0;
    }

    value /= (10 ** decimalPrecision);

    return currency(value, format, decimalPlaces);
};
