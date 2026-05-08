<script setup lang="ts">
import type { Player } from '@/types';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        player: Pick<Player, 'name' | 'image_url'>;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    { size: 'md' },
);

const sizeClass = computed(() =>
    props.size === 'sm' ? 'w-7 h-7 text-xs' : props.size === 'lg' ? 'w-14 h-14 text-lg' : 'w-10 h-10 text-sm',
);

const initials = computed(() =>
    props.player.name
        .split(/\s+/)
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <div
        :class="[
            sizeClass,
            'inline-flex items-center justify-center border-2 border-border bg-muted font-bold uppercase tracking-wider text-foreground',
        ]"
    >
        <img
            v-if="player.image_url"
            :src="player.image_url"
            :alt="player.name"
            class="h-full w-full object-cover"
        />
        <span v-else>{{ initials }}</span>
    </div>
</template>
