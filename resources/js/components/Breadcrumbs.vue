<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

const props = defineProps<Props>();

/** On small screens only the last two crumbs are shown to keep the header on one line. */
function hiddenOnMobile(index: number): boolean {
    return index < props.breadcrumbs.length - 2;
}
</script>

<template>
    <Breadcrumb class="min-w-0">
        <BreadcrumbList class="flex-nowrap">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem
                    :class="[
                        'min-w-0',
                        hiddenOnMobile(index) ? 'hidden md:inline-flex' : '',
                    ]"
                >
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage
                            class="max-w-[18rem] truncate font-medium"
                            :title="item.title"
                            >{{ item.title }}</BreadcrumbPage
                        >
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <Link
                                :href="item.href"
                                class="max-w-[14rem] truncate"
                                :title="item.title"
                                >{{ item.title }}</Link
                            >
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                    :class="hiddenOnMobile(index) ? 'hidden md:list-item' : ''"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
