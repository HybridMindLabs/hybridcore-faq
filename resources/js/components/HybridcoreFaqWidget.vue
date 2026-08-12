<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleHelp, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { useTheme } from '@/composables/useTheme';
import { useLocale } from '@/composables/useLocale';

interface Entry { id: number; question: string }
defineProps<{ entries: Entry[] }>();

const { theme } = useTheme();
const { t } = useLocale();
const dark = computed(() => theme.value === 'dark');
</script>

<template>
    <div v-if="entries.length" class="rounded-xl border overflow-hidden" :class="dark ? 'border-zinc-800/70 bg-[#111113]' : 'border-zinc-200 bg-white'">
        <Link :href="route('faq.index')"
            class="px-5 py-3 border-b flex items-center gap-2 transition-colors"
            :class="dark ? 'border-zinc-800/60 hover:bg-white/[0.02]' : 'border-zinc-100 hover:bg-zinc-50'">
            <CircleHelp :size="14" :stroke-width="2" class="text-blue-400" />
            <span class="text-[13px] font-bold" :class="dark ? 'text-zinc-100' : 'text-zinc-900'">{{ t('ext.faq.widget_title') }}</span>
        </Link>

        <Link v-for="entry in entries" :key="entry.id" :href="route('faq.index') + '#faq-' + entry.id"
            class="flex items-center gap-2 px-5 py-2.5 border-b last:border-0 text-[13px] font-medium transition-colors"
            :class="dark ? 'border-zinc-800/40 text-zinc-300 hover:text-zinc-100 hover:bg-white/[0.02]' : 'border-zinc-50 text-zinc-700 hover:text-zinc-900 hover:bg-zinc-50'">
            <span class="flex-1 min-w-0 truncate">{{ entry.question }}</span>
            <ChevronRight :size="13" :stroke-width="2" class="shrink-0" :class="dark ? 'text-zinc-700' : 'text-zinc-300'" />
        </Link>

        <Link :href="route('faq.index')"
            class="block px-5 py-2.5 text-center text-[12px] font-bold border-t transition-colors"
            :class="dark ? 'border-zinc-800/60 text-blue-400 hover:bg-white/[0.02]' : 'border-zinc-100 text-blue-600 hover:bg-zinc-50'">
            {{ t('ext.faq.widget_link') }}
        </Link>
    </div>
</template>
