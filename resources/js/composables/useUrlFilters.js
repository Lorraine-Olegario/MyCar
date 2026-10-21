import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import { cleanQuery } from '../utils/filterQuery';

/**
 * Filtros refletidos na query string: cada mudança navega com `replace`, preservando
 * o estado da página e a rolagem, e os filtros sobrevivem a recarregar/compartilhar o link.
 */
export function useUrlFilters(path, initial = {}, extra = {}) {
    const filters = reactive({ ...initial });
    const loading = ref(false);

    const apply = () => {
        router.get(path, { ...cleanQuery(extra), ...cleanQuery(filters) }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                loading.value = true;
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    };

    const set = (key, value) => {
        filters[key] = value;
        apply();
    };

    const reset = (defaults = {}) => {
        Object.keys(filters).forEach((key) => {
            filters[key] = defaults[key] ?? '';
        });
        apply();
    };

    return { filters, loading, apply, set, reset };
}
