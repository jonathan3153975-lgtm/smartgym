/**
 * CEP autocomplete shared by every address form.
 *
 * Usage:
 *   <div data-cep-scope>
 *     <input data-cep>
 *     <input data-cep-field="street">
 *     <input data-cep-field="neighborhood">
 *     <input data-cep-field="city">
 *     <input data-cep-field="state">
 *     <input data-cep-field="number">
 *   </div>
 */

const cepCache = new Map();

function onlyDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatCep(value) {
    return onlyDigits(value)
        .slice(0, 8)
        .replace(/^(\d{5})(\d)/, '$1-$2');
}

function setError(input, hasError) {
    input.style.borderColor = hasError ? 'var(--color-red)' : '';
    input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
}

function fillAddress(scope, data) {
    const values = {
        street: data.logradouro,
        neighborhood: data.bairro,
        city: data.localidade,
        state: data.uf,
    };

    Object.entries(values).forEach(([field, value]) => {
        if (!value) {
            return;
        }

        const input = scope.querySelector(`[data-cep-field="${field}"]`);

        if (input) {
            input.value = value;
        }
    });

    const numberInput = scope.querySelector('[data-cep-field="number"]');

    if (numberInput) {
        numberInput.focus();
    }
}

async function lookupCep(input) {
    const scope = input.closest('[data-cep-scope]');
    const cep = onlyDigits(input.value);

    if (!scope || cep.length !== 8) {
        setError(input, false);
        return;
    }

    if (cepCache.has(cep)) {
        fillAddress(scope, cepCache.get(cep));
        setError(input, false);
        return;
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);

        if (!response.ok) {
            throw new Error('CEP lookup failed');
        }

        const data = await response.json();

        if (data.erro) {
            setError(input, true);
            return;
        }

        cepCache.set(cep, data);
        fillAddress(scope, data);
        setError(input, false);
    } catch (error) {
        setError(input, true);
    }
}

export function initCepAutocomplete(root = document) {
    root.querySelectorAll('input[data-cep]').forEach((input) => {
        if (input.dataset.cepInitialized === 'true') {
            return;
        }

        input.dataset.cepInitialized = 'true';
        input.setAttribute('maxlength', '9');
        input.setAttribute('inputmode', 'numeric');

        input.addEventListener('input', () => {
            input.value = formatCep(input.value);
            setError(input, false);

            if (onlyDigits(input.value).length === 8) {
                lookupCep(input);
            }
        });

        if (onlyDigits(input.value).length === 8) {
            lookupCep(input);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initCepAutocomplete());
} else {
    initCepAutocomplete();
}
