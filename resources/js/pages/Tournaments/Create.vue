<script setup lang="ts">
import PlayerAutocomplete from '@/components/PlayerAutocomplete.vue';
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import AppShell from '@/layouts/AppShell.vue';
import type { Player } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{
    players: Player[];
}>();

const roster = ref<Player[]>([]);

const form = useForm({
    name: 'Game Night',
    player_ids: [] as number[],
});

const addPlayer = (player: Player) => {
    if (roster.value.find((p) => p.id === player.id)) return;
    roster.value.push(player);
    form.player_ids = roster.value.map((p) => p.id);
};

const removePlayer = (id: number) => {
    roster.value = roster.value.filter((p) => p.id !== id);
    form.player_ids = roster.value.map((p) => p.id);
};

const excludeIds = computed(() => roster.value.map((p) => p.id));

const submit = () => {
    form.post('/tournaments');
};
</script>

<template>
    <Head title="Start Tournament" />

    <AppShell>
        <div class="mx-auto max-w-2xl space-y-6 p-8">
            <header class="border-b-2 border-border pb-4">
                <h1 class="text-3xl font-bold tracking-widest uppercase">
                    New Tournament
                </h1>
                <p
                    class="mt-1 text-xs tracking-wider text-muted-foreground uppercase"
                >
                    Name it. Stack the lobby. Start scoring.
                </p>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label
                        class="mb-2 block text-xs tracking-wider text-muted-foreground uppercase"
                        >Name</label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border-2 border-border bg-background px-3 py-2 font-mono focus:border-primary focus:outline-none"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        Roster ({{ roster.length }})
                    </label>
                    <PlayerAutocomplete
                        :exclude-ids="excludeIds"
                        placeholder="Type a name..."
                        @select="addPlayer"
                    />
                    <p class="mt-2 text-xs text-muted-foreground">
                        Type to search existing players. Press Enter on an
                        unmatched name to create.
                    </p>

                    <div
                        v-if="roster.length"
                        class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-3"
                    >
                        <div
                            v-for="p in roster"
                            :key="p.id"
                            class="flex items-center justify-between border-2 border-border bg-card px-3 py-2"
                        >
                            <div class="flex items-center gap-2">
                                <PlayerAvatar :player="p" size="sm" />
                                <span class="truncate text-sm">{{
                                    p.name
                                }}</span>
                            </div>
                            <button
                                type="button"
                                class="text-xs tracking-wider text-muted-foreground uppercase hover:text-destructive"
                                @click="removePlayer(p.id)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="form.errors.player_ids"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.player_ids }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing || roster.length < 2"
                    class="w-full border-2 border-primary bg-primary px-4 py-2 font-bold tracking-widest text-primary-foreground uppercase transition-colors hover:bg-background hover:text-primary disabled:opacity-50"
                >
                    Start Tournament
                </button>
            </form>
        </div>
    </AppShell>
</template>
