<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon } from '@heroicons/vue/16/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import {
    ChartBarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    TagIcon,
    ChevronLeftIcon,
    ChevronDoubleLeftIcon,
    ChevronRightIcon,
    ChevronDoubleRightIcon,
    ClockIcon,
} from '@heroicons/vue/20/solid';
import DateRangePicker from '@/packages/ui/src/Input/DateRangePicker.vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { computed, ref, watch } from 'vue';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { storeToRefs } from 'pinia';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import {
    api,
    type TimeEntriesQueryParams,
    type TimeEntry,
    type TimeEntryResponse,
} from '@/packages/api/src';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { useTagsStore } from '@/utils/useTags';
import { useSessionStorage } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'radix-vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import ReportingExportButton from '@/Components/Common/Reporting/ReportingExportButton.vue';
import type { ExportFormat } from '@/types/reporting';
import { useNotificationsStore } from '@/utils/notification';

const startDate = useSessionStorage<string>(
    'reporting-start-date',
    getLocalizedDayJs(getDayJsInstance()().format()).subtract(14, 'd').format()
);
const endDate = useSessionStorage<string>(
    'reporting-end-date',
    getLocalizedDayJs(getDayJsInstance()().format()).format()
);
const selectedTags = ref<string[]>([]);
const selectedProjects = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);
const selectedTasks = ref<string[]>([]);
const selectedClients = ref<string[]>([]);
const billable = ref<'true' | 'false' | null>(null);

const pageLimit = 60;
const currentPage = ref(1);

function getFilterAttributes(): TimeEntriesQueryParams {
    let params: TimeEntriesQueryParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
        active: 'false',
        limit: pageLimit,
        offset: currentPage.value * pageLimit - pageLimit,
    };
    params = {
        ...params,
        member_ids:
            selectedMembers.value.length > 0
                ? selectedMembers.value
                : undefined,
        project_ids:
            selectedProjects.value.length > 0
                ? selectedProjects.value
                : undefined,
        task_ids:
            selectedTasks.value.length > 0 ? selectedTasks.value : undefined,
        client_ids:
            selectedClients.value.length > 0
                ? selectedClients.value
                : undefined,
        tag_ids: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        billable: billable.value !== null ? billable.value : undefined,
    };
    return params;
}

const { handleApiRequestNotifications } = useNotificationsStore();
const { tags } = storeToRefs(useTagsStore());

const { data: timeEntryResponse } = useQuery<TimeEntryResponse>({
    queryKey: ['timeEntry', 'detailed-report-60'],
    enabled: !!getCurrentOrganizationId(),
    queryFn: () =>
        api.getTimeEntries({
            params: {
                organization: getCurrentOrganizationId() || '',
            },
            queries: getFilterAttributes(),
        }),
});

const timeEntries = computed<TimeEntry[]>(() => {
    return timeEntryResponse?.value?.data ?? [];
});

const totalPages = computed(() => {
    return timeEntryResponse?.value?.meta?.total ?? 1;
});

const groupedEntries = computed(() => {
    const map = new Map<string, TimeEntry[]>();

    for (const entry of timeEntries.value) {
        if (!entry.start) {
            continue;
        }

        const dayKey = getLocalizedDayJs(entry.start)
            .startOf('day')
            .format();
        const existing = map.get(dayKey) ?? [];
        existing.push(entry);
        map.set(dayKey, existing);
    }

    return Array.from(map.entries())
        .sort(
            (a, b) =>
                getDayJsInstance()(b[0]).valueOf() -
                getDayJsInstance()(a[0]).valueOf()
        )
        .map(([date, entries]) => ({
            date,
            entries: [...entries].sort((entryA, entryB) => {
                const startA = entryA.start
                    ? getDayJsInstance()(entryA.start).valueOf()
                    : 0;
                const startB = entryB.start
                    ? getDayJsInstance()(entryB.start).valueOf()
                    : 0;
                return startA - startB;
            }),
        }));
});

const queryClient = useQueryClient();
async function updateFilteredTimeEntries() {
    await queryClient.invalidateQueries({
        queryKey: ['timeEntry', 'detailed-report-60'],
    });
}


watch(currentPage, () => {
    updateFilteredTimeEntries();
});

function handleFiltersChanged() {
    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    updateFilteredTimeEntries();
}

async function downloadExport(format: ExportFormat) {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        const response = await handleApiRequestNotifications(
            () =>
                api.exportTimeEntries({
                    params: {
                        organization: organizationId,
                    },
                    queries: {
                        ...getFilterAttributes(),
                        format: format,
                    },
                }),
            'Export successful',
            'Export failed'
        );
        window.open(response.download_url, '_self')?.focus();
    }
}

function formatDateLabel(date: string) {
    return getLocalizedDayJs(date).format('MMM D, YYYY');
}

function formatTimeRange(entry?: TimeEntry) {
    if (!entry) {
        return '—';
    }

    const startLabel = entry.start
        ? getLocalizedDayJs(entry.start).format('HH:mm')
        : '—';
    const endLabel = entry.end
        ? getLocalizedDayJs(entry.end).format('HH:mm')
        : '—';

    return `${startLabel} - ${endLabel}`;
}

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}
</script>

<template>
    <AppLayout
        title="Reporting"
        data-testid="reporting_view"
        class="overflow-hidden">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <TabBar>
                    <TabBarItem @click="router.visit(route('reporting'))"
                        >Overview
                    </TabBarItem>
                    <TabBarItem
                        @click="router.visit(route('reporting.detailed'))"
                        >Detailed
                    </TabBarItem>
                    <TabBarItem
                        @click="router.visit(route('reporting.detailed60'))"
                        active>
                        Extended
                    </TabBarItem>
                </TabBar>
            </div>
            <ReportingExportButton
                :download="downloadExport"></ReportingExportButton>
        </MainContainer>
        <div class="py-2.5 w-full border-b border-default-background-separator">
            <MainContainer
                class="sm:flex space-y-4 sm:space-y-0 justify-between">
                <div
                    class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                    <div class="text-sm font-medium">Filters</div>
                    <MemberMultiselectDropdown
                        @submit="handleFiltersChanged"
                        v-model="selectedMembers">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedMembers.length"
                                :active="selectedMembers.length > 0"
                                title="Members"
                                :icon="UserGroupIcon"></ReportingFilterBadge>
                        </template>
                    </MemberMultiselectDropdown>
                    <ProjectMultiselectDropdown
                        @submit="handleFiltersChanged"
                        v-model="selectedProjects">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedProjects.length"
                                :active="selectedProjects.length > 0"
                                title="Projects"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ProjectMultiselectDropdown>
                    <TaskMultiselectDropdown
                        @submit="handleFiltersChanged"
                        v-model="selectedTasks">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedTasks.length"
                                :active="selectedTasks.length > 0"
                                title="Tasks"
                                :icon="CheckCircleIcon"></ReportingFilterBadge>
                        </template>
                    </TaskMultiselectDropdown>
                    <ClientMultiselectDropdown
                        @submit="handleFiltersChanged"
                        v-model="selectedClients">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                title="Clients"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ClientMultiselectDropdown>
                    <TagDropdown
                        @submit="handleFiltersChanged"
                        :createTag
                        v-model="selectedTags"
                        :tags="tags">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedTags.length"
                                :active="selectedTags.length > 0"
                                title="Tags"
                                :icon="TagIcon"></ReportingFilterBadge>
                        </template>
                    </TagDropdown>

                    <SelectDropdown
                        @changed="handleFiltersChanged"
                        v-model="billable"
                        :get-key-from-item="(item) => item.value"
                        :get-name-for-item="(item) => item.label"
                        :items="[
                            {
                                label: 'Both',
                                value: null,
                            },
                            {
                                label: 'Billable',
                                value: 'true',
                            },
                            {
                                label: 'Non Billable',
                                value: 'false',
                            },
                        ]">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :active="billable !== null"
                                :title="
                                    billable === 'false'
                                        ? 'Non Billable'
                                        : 'Billable'
                                "
                                :icon="BillableIcon"></ReportingFilterBadge>
                        </template>
                    </SelectDropdown>
                </div>
                <div>
                    <DateRangePicker
                        v-model:start="startDate"
                        v-model:end="endDate"
                        @submit="handleFiltersChanged"></DateRangePicker>
                </div>
            </MainContainer>
        </div>
        <MainContainer class="py-6">
            <div v-if="groupedEntries.length > 0" class="overflow-x-auto">
                <table
                    class="w-full border border-default-background-separator rounded-lg text-sm text-text-secondary">
                    <thead class="bg-tertiary text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Date
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Morning
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Afternoon
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="group in groupedEntries"
                            :key="group.date"
                            class="border-t border-default-background-separator">
                            <td class="px-4 py-3 font-semibold text-white">
                                {{ formatDateLabel(group.date) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTimeRange(group.entries[0]) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatTimeRange(group.entries[1]) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="text-center py-12">
                <ClockIcon class="w-8 text-icon-default inline pb-2"></ClockIcon>
                <h3 class="text-white font-semibold">No time entries found</h3>
                <p class="pb-5">Adjust the filters to see more time entries!</p>
            </div>
        </MainContainer>

        <PaginationRoot
            :total="totalPages"
            :items-per-page="pageLimit"
            class="flex justify-center items-center py-8"
            v-model:page="currentPage"
            :sibling-count="1"
            show-edges>
            <PaginationList
                v-slot="{ items }"
                class="flex items-center space-x-1 relative">
                <div
                    class="pr-2 flex items-center space-x-1 border-r border-border-primary mr-1">
                    <PaginationFirst class="navigation-item">
                        <ChevronDoubleLeftIcon class="w-4">
                        </ChevronDoubleLeftIcon>
                    </PaginationFirst>
                    <PaginationPrev class="mr-4 navigation-item">
                        <ChevronLeftIcon
                            class="w-4 text-text-tertiary hover:text-text-primary">
                        </ChevronLeftIcon>
                    </PaginationPrev>
                </div>
                <template v-for="(page, index) in items">
                    <PaginationListItem
                        v-if="page.type === 'page'"
                        :key="index"
                        class="pagination-item"
                        :value="page.value">
                        {{ page.value }}
                    </PaginationListItem>
                    <PaginationEllipsis
                        v-else
                        :key="page.type"
                        :index="index"
                        class="PaginationEllipsis">
                        <div class="px-2">&#8230;</div>
                    </PaginationEllipsis>
                </template>
                <div
                    class="!ml-2 pl-2 flex items-center space-x-1 border-l border-border-primary">
                    <PaginationNext class="navigation-item">
                        <ChevronRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronRightIcon>
                    </PaginationNext>
                    <PaginationLast class="navigation-item">
                        <ChevronDoubleRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronDoubleRightIcon>
                    </PaginationLast>
                </div>
            </PaginationList>
        </PaginationRoot>
    </AppLayout>
</template>
<style lang="postcss">
.navigation-item {
    @apply bg-quaternary h-8 w-8 flex items-center justify-center rounded border border-border-primary text-text-tertiary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-white/80;
}

.pagination-item {
    @apply bg-secondary h-8 w-8 flex items-center justify-center rounded border border-border-tertiary text-text-secondary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-white/80;
}
.pagination-item[data-selected] {
    @apply text-white bg-accent-300/10 border border-accent-300/20 rounded-md font-medium hover:bg-accent-300/20 active:bg-accent-300/20 outline-0 focus-visible:ring-2 focus:ring-white/80 transition ease-in-out duration-150;
}
</style>
