<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CircleHelp, ThumbsUp, Search } from '@lucide/vue';
import { computed } from 'vue';
import { useTheme } from '@/composables/useTheme';
import { useLocale } from '@/composables/useLocale';

interface Entry { id: number; category: string; question: string; helpful_count: number }
defineProps<{ entries: Entry[] }>();

const { theme } = useTheme();
const { t } = useLocale();
const dark = computed(() => theme.value === 'dark');

const CATEGORY_HUES = [
    'bg-blue-500/10 text-blue-400', 'bg-violet-500/10 text-violet-400',
    'bg-amber-500/10 text-amber-400', 'bg-emerald-500/10 text-emerald-400',
];
function categoryHue(category: string): string {
    let h = 0;
    for (let i = 0; i < category.length; i++) h = (h + category.charCodeAt(i)) % CATEGORY_HUES.length;
    return CATEGORY_HUES[h];
}
</script>

<template>
    <div v-if="entries.length" class="rounded-xl border overflow-hidden" :class="dark ? 'border-zinc-800/70 bg-[#111113]' : 'border-zinc-200 bg-white'">
        <div class="px-5 pt-4 pb-3">
            <div class="flex items-center gap-2">
                <CircleHelp :size="14" :stroke-width="2" class="text-blue-400" />
                <span class="text-[13px] font-bold" :class="dark ? 'text-zinc-100' : 'text-zinc-900'">{{ t('ext.faq.widget_title') }}</span>
            </div>
            <p class="text-[11px] mt-1" :class="dark ? 'text-zinc-600' : 'text-zinc-400'">{{ t('ext.faq.popular_subtitle') }}</p>
        </div>

        <Link v-for="entry in entries" :key="entry.id" :href="route('faq.index') + '#faq-' + entry.id"
            class="flex items-start gap-2.5 px-5 py-2.5 border-t text-[13px] transition-colors"
            :class="dark ? 'border-zinc-800/50 hover:bg-white/[0.02]' : 'border-zinc-100 hover:bg-zinc-50'">
            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded shrink-0 mt-0.5" :class="categoryHue(entry.category)">{{ entry.category }}</span>
            <span class="flex-1 min-w-0 font-medium leading-snug" :class="dark ? 'text-zinc-300' : 'text-zinc-700'">{{ entry.question }}</span>
            <span class="flex items-center gap-1 shrink-0 text-[11px] font-mono font-bold mt-0.5" :class="dark ? 'text-zinc-600' : 'text-zinc-400'">
                <ThumbsUp :size="10" :stroke-width="2.5" /> {{ entry.helpful_count }}
            </span>
        </Link>

        <Link :href="route('faq.index')"
            class="flex items-center justify-center gap-1.5 px-5 py-2.5 text-center text-[12px] font-bold border-t transition-colors"
            :class="dark ? 'border-zinc-800/60 text-blue-400 hover:bg-white/[0.02]' : 'border-zinc-100 text-blue-600 hover:bg-zinc-50'">
            <Search :size="12" :stroke-width="2.5" /> {{ t('ext.faq.widget_link') }}
        </Link>
    </div>
</template>
