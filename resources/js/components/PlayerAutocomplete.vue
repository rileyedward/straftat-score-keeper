<script setup lang="ts">
import type { Player } from '@/types';
import { computed, ref, useTemplateRef, watch } from 'vue';

const props = defineProps<{
    excludeIds?: number[];
    placeholder?: string;
}>();

const emit = defineEmits<{
    select: [player: Player];
}>();

const inputRef = useTemplateRef<HTMLInputElement>('input');

const query = ref('');
const results = ref<Player[]>([]);
const loading = ref(false);
const open = ref(false);
let debounceId: ReturnType<typeof setTimeout> | undefined;
let blurId: ReturnType<typeof setTimeout> | undefined;

const exclude = computed(() => new Set(props.excludeIds ?? []));

const filtered = computed(() =>
    results.value.filter((p) => !exclude.value.has(p.id)),
);

const fetchResults = async (q: string) => {
    loading.value = true;
    try {
        const res = await fetch(
            `/api/players/search?q=${encodeURIComponent(q)}`,
            {
                headers: { Accept: 'application/json' },
            },
        );
        const data = await res.json();
        results.value = data.players;
    } finally {
        loading.value = false;
    }
};

const onFocus = () => {
    if (blurId) clearTimeout(blurId);
    open.value = true;
    fetchResults(query.value);
};

const onBlur = () => {
    blurId = setTimeout(() => {
        open.value = false;
    }, 150);
};

watch(query, (q) => {
    if (debounceId) clearTimeout(debounceId);
    debounceId = setTimeout(() => fetchResults(q), 150);
});

const exactMatch = computed(() =>
    filtered.value.some(
        (p) => p.name.toLowerCase() === query.value.trim().toLowerCase(),
    ),
);

const canCreate = computed(
    () => query.value.trim().length > 0 && !exactMatch.value,
);

const pickPlayer = (player: Player) => {
    emit('select', player);
    query.value = '';
    results.value = [];
    open.value = false;
    inputRef.value?.blur();
};

const createNew = async () => {
    const name = query.value.trim();
    if (!name) return;
    const res = await fetch('/players', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN':
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ name }),
    });
    if (!res.ok) return;
    const data = await res.json();
    pickPlayer(data.player);
};
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            :placeholder="placeholder ?? 'Add player...'"
            class="w-full border-2 border-border bg-background px-3 py-2 font-mono text-foreground focus:border-primary focus:outline-none"
            ref="input"
            @focus="onFocus"
            @blur="onBlur"
            @keydown.enter.prevent="canCreate && createNew()"
        />

        <div
            v-if="open && (filtered.length || canCreate || loading)"
            class="absolute z-10 mt-1 w-full border-2 border-border bg-card"
            @mousedown.prevent
        >
            <button
                v-for="p in filtered"
                :key="p.id"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                @click="pickPlayer(p)"
            >
                {{ p.name }}
            </button>
            <button
                v-if="canCreate"
                type="button"
                class="block w-full border-t-2 border-border px-3 py-2 text-left text-xs tracking-wider text-primary uppercase transition-colors hover:bg-primary hover:text-primary-foreground"
                @click="createNew"
            >
                + Create &ldquo;{{ query.trim() }}&rdquo;
            </button>
            <div
                v-if="loading && !filtered.length"
                class="px-3 py-2 text-xs text-muted-foreground"
            >
                Searching...
            </div>
        </div>
    </div>
</template>
