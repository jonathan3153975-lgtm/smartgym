/**
 * Decimal mask with comma separator for numeric inputs.
 *
 * Usage:
 *   <input type="text" data-decimal-input="3">
 *   <input type="text" data-decimal-input="2" value="1.75">
 *
 * Values are displayed with a comma and limited to the given number of
 * decimals. The backend is responsible for normalizing the comma to a dot.
 */

function formatDecimalValue(input, decimals) {
    let value = String(input.value ?? '')
        .replace(/\./g, ',')
        .replace(/[^\d,]/g, '');

    const firstComma = value.indexOf(',');

    if (firstComma !== -1) {
        const integerPart = value.slice(0, firstComma);
        const decimalPart = value.slice(firstComma + 1).replace(/,/g, '').slice(0, decimals);

        value = integerPart + ',' + decimalPart;
    }

    input.value = value;
}

export function initDecimalMasks(root = document) {
    root.querySelectorAll('input[data-decimal-input]').forEach((input) => {
        if (input.dataset.decimalInitialized === 'true') {
            return;
        }

        input.dataset.decimalInitialized = 'true';

        const decimals = Number(input.dataset.decimalInput || 3);
        input.setAttribute('inputmode', 'decimal');

        const apply = () => formatDecimalValue(input, decimals);

        input.addEventListener('input', apply);
        input.addEventListener('blur', () => {
            input.value = input.value.replace(/,+$/, '');
        });

        apply();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initDecimalMasks());
} else {
    initDecimalMasks();
}
