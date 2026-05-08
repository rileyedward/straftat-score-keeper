<script setup lang="ts">
import PlayerAvatar from '@/components/PlayerAvatar.vue';
import AppShell from '@/layouts/AppShell.vue';
import type { Player } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    player: Player;
    stats: {
        matches_played: number;
        tournaments_played: number;
    };
}>();

const form = useForm({ name: props.player.name });

const fileInput = ref<HTMLInputElement | null>(null);

const save = () => form.patch(`/players/${props.player.id}`, { preserveScroll: true });

const onUpload = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('image', file);
    router.post(`/players/${props.player.id}/image`, fd, { preserveScroll: true, forceFormData: true });
};
</script>

<template>
    <Head :title="player.name" />

    <AppShell>
        <div class="mx-auto max-w-xl space-y-6 p-8">
            <header class="border-b-2 border-border pb-4">
                <h1 class="text-xs uppercase tracking-widest text-muted-foreground">Player</h1>
                <div class="mt-2 flex items-center gap-3">
                    <PlayerAvatar :player="player" size="lg" />
                    <div>
                        <h2 class="text-3xl font-bold uppercase tracking-widest">{{ player.name }}</h2>
                        <div class="text-xs text-muted-foreground">
                            {{ stats.matches_played }} matches · {{ stats.tournaments_played }} tournaments
                        </div>
                    </div>
                </div>
            </header>

            <section class="space-y-3 border-2 border-border bg-card p-4">
                <h3 class="text-xs uppercase tracking-widest text-muted-foreground">Edit Profile</h3>

                <form class="space-y-3" @submit.prevent="save">
                    <div>
                        <label class="mb-1 block text-xs uppercase tracking-wider text-muted-foreground">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border-2 border-border bg-background px-3 py-2 font-mono focus:border-primary focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="border-2 border-primary bg-primary px-4 py-2 text-xs font-bold uppercase tracking-widest text-primary-foreground hover:bg-background hover:text-primary disabled:opacity-50"
                    >
                        Save Name
                    </button>
                </form>

                <div class="border-t-2 border-border pt-3">
                    <label class="mb-1 block text-xs uppercase tracking-wider text-muted-foreground">Profile Image</label>
                    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onUpload" />
                    <button
                        type="button"
                        class="border-2 border-border px-3 py-2 text-xs uppercase tracking-wider text-foreground hover:border-primary hover:text-primary"
                        @click="fileInput?.click()"
                    >
                        Upload Image
                    </button>
                </div>
            </section>
        </div>
    </AppShell>
</template>
