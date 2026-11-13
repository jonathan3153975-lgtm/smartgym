/**
 * Brazilian currency mask (BRL) shared by monetary fields.
 *
 * Usage:
 *   <input type="text" data-currency data-currency-target="price_value">
 *   <input type="hidden" id="price_value" name="price">
 */

function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatFromDigits(digits) {
    if (!digits) {
        return '';
    }

    return (Number(digits) / 100).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function targetFor(input) {
    return input.dataset.currencyTarget
        ? document.getElementById(input.dataset.currencyTarget)
        : null;
}

function syncFromDisplay(input) {
    const digits = onlyDigits(input.value);
    const target = targetFor(input);

    input.value = formatFromDigits(digits);

    if (target) {
        target.value = digits ? (Number(digits) / 100).toFixed(2) : '';
    }
}

export function initCurrencyMasks(root = document) {
    root.querySelectorAll('input[data-currency]').forEach((input) => {
        if (input.dataset.currencyInitialized === 'true') {
            return;
        }

        input.dataset.currencyInitialized = 'true';
        input.setAttribute('inputmode', 'decimal');

        input.addEventListener('input', () => syncFromDisplay(input));
        syncFromDisplay(input);
    });
}

/**
 * Set a currency input from a numeric value (used by edit forms).
 */
export function setCurrencyValue(input, value) {
    const target = targetFor(input);

    if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
        input.value = '';

        if (target) {
            target.value = '';
        }

        return;
    }

    const numeric = Number(value);

    input.value = numeric.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    if (target) {
        target.value = numeric.toFixed(2);
    }
}

window.SmartGymSetCurrency = setCurrencyValue;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initCurrencyMasks());
} else {
    initCurrencyMasks();
}
