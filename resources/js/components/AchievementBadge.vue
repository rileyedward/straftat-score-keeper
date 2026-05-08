<script setup lang="ts">
import type { Achievement } from '@/types';

defineProps<{
    achievement: Achievement;
}>();
</script>

<template>
    <div class="border-2 border-accent bg-card p-4">
        <div class="text-xs uppercase tracking-widest text-accent">★ {{ achievement.name }}</div>
        <div class="mt-1 text-xl font-bold uppercase">{{ achievement.player_name }}</div>
        <div class="mt-2 text-xs text-muted-foreground">{{ achievement.description }}</div>
        <div class="mt-1 font-mono text-sm text-foreground">{{ achievement.detail }}</div>
    </div>
</template>
