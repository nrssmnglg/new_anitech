<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    logs: { type: Object, required: true }, filters: { type: Object, required: true },
    activeFilterCount: { type: Number, required: true }, moduleOptions: { type: Array, required: true },
    eventOptions: { type: Array, required: true }, actorOptions: { type: Array, required: true },
    summary: { type: Object, required: true }, urls: { type: Object, required: true },
});
const form = reactive({ ...props.filters });
const applying = ref(false);
const datesOpen = ref(false);
const selectedId = ref(null);
const selected = computed(() => props.logs.data.find(log => log.id === selectedId.value) || props.logs.data[0] || null);
watch(() => props.filters, filters => Object.assign(form, filters));
const cards = computed(() => [
    { label: "Today's volume", value: `${props.summary.today ?? 0} Actions`, note: 'Activity recorded since midnight', icon: 'history', tone: 'mint' },
    { label: 'Recorded activity', value: `${props.summary.total ?? 0} Actions`, note: 'Complete municipal audit history', icon: 'people', tone: 'green' },
    { label: 'Access activity', value: `${props.summary.auth ?? 0} Events`, note: 'Recorded sign-in and access events', icon: 'shield', tone: 'peach' },
]);
const paths = {
    shield: 'M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Zm-4 9 3 3 5-6',
    history: 'M3 11a9 9 0 1 1 2 7M3 4v7h7m2-5v6l4 2',
    people: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m18 0v-2a4 4 0 0 0-3-3.87M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-7a4 4 0 0 1 0 7.75',
};
function applyFilters() {
    if (applying.value) return;
    applying.value = true;
    router.get(props.urls.index, Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '' && value != null)), {
        preserveScroll: true, preserveState: true, replace: true, onFinish: () => { applying.value = false; },
    });
}
function resetFilters() {
    Object.keys(form).forEach(key => { form[key] = ''; });
    applyFilters();
}
function initials(name) { return String(name || 'System').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase(); }
function roleLabel(role) { return role === 'Staff' ? 'Office Staff' : role || 'System'; }
function eventTone(event) {
    if (/delete|reject|fail/i.test(event)) return 'danger';
    if (/create|approve|success/i.test(event)) return 'success';
    return 'neutral';
}
</script>

<template>
    <Head title="Audit Trail" />
    <AdminLayout title="Audit Trail">
        <div class="audit-page">
            <section class="summary-grid" aria-label="Activity summary">
                <article v-for="card in cards" :key="card.label" class="summary-card">
                    <div><p class="eyebrow">{{ card.label }}</p><h2>{{ card.value }}</h2><p class="card-note">{{ card.note }}</p></div>
                    <span class="card-icon" :class="card.tone"><svg viewBox="0 0 24 24"><path :d="paths[card.icon]" /></svg></span>
                </article>
            </section>
            <form class="filter-panel" @submit.prevent="applyFilters">
                <div class="filter-grid">
                    <div class="search-control"><svg viewBox="0 0 24 24"><circle cx="10" cy="10" r="6" /><path d="m15 15 5 5" /></svg><input v-model="form.search" aria-label="Search activity" placeholder="Search activity…" type="search"></div>
                    <button type="button" class="filter-control" :aria-expanded="datesOpen" @click="datesOpen = !datesOpen">{{ form.date_from || form.date_to ? 'Custom date range' : 'All dates' }} <span aria-hidden="true">▦</span></button>
                    <select v-model="form.actor_user_id" aria-label="Filter by user" @change="applyFilters"><option value="">All Users</option><option :value="null" hidden>All Users</option><option v-for="option in actorOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                    <select v-model="form.module" aria-label="Filter by module" @change="applyFilters"><option value="">All Modules</option><option :value="null" hidden>All Modules</option><option v-for="option in moduleOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                    <select v-model="form.event" aria-label="Filter by action" @change="applyFilters"><option value="">All Actions</option><option :value="null" hidden>All Actions</option><option v-for="option in eventOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                    <button class="button primary" :disabled="applying">{{ applying ? 'Loading…' : 'Search' }}</button>
                </div>
                <div v-if="datesOpen" class="date-controls"><label>From <input v-model="form.date_from" type="date" :max="form.date_to || undefined"></label><label>To <input v-model="form.date_to" type="date" :min="form.date_from || undefined"></label><button class="button" :disabled="applying">Apply dates</button></div>
                <div class="filter-footer"><p><span class="count-badge">Showing {{ logs.total }} activities</span><span class="filter-caption">{{ activeFilterCount ? `${activeFilterCount} active filters` : 'Municipal activity history' }}</span></p><button type="button" :disabled="applying" @click="resetFilters">↻ Clear Filters</button></div>
            </form>
            <div class="activity-layout">
                <section class="activity-table-panel" aria-label="Audit activities" :aria-busy="applying">
                    <div class="table-scroll"><table><thead><tr><th>Date &amp; time</th><th>User</th><th>Role</th><th>Module</th><th>Action</th></tr></thead>
                        <tbody><tr v-for="log in logs.data" :key="log.id" :class="{ selected: selected?.id === log.id }" @click="selectedId = log.id">
                            <td><time>{{ log.createdAt }}</time></td>
                            <td><button class="actor-button" :aria-pressed="selected?.id === log.id" @click.stop="selectedId = log.id"><span class="avatar" :class="{ staff: log.actorRole !== 'Admin' }">{{ initials(log.actorName) }}</span>{{ log.actorName }}</button></td>
                            <td><span class="role-badge" :class="{ staff: log.actorRole !== 'Admin' }">{{ roleLabel(log.actorRole) }}</span></td><td>{{ log.module.label }}</td><td><span class="event-badge" :class="eventTone(log.event.value)">{{ log.event.label }}</span></td>
                        </tr></tbody></table></div>
                    <div v-if="!logs.data.length" class="empty-state"><h2>No activity found</h2><p>Try another search or clear the filters.</p><button class="button" @click="resetFilters">Clear filters</button></div>
                    <footer class="pagination"><p>{{ logs.from || 0 }}–{{ logs.to || 0 }} of {{ logs.total }} activities</p><nav aria-label="Activity pages"><template v-for="link in logs.links" :key="link.label"><Link v-if="link.url" :href="link.url" :class="{ current: link.active }" :aria-current="link.active ? 'page' : undefined" preserve-scroll v-html="link.label" /><span v-else v-html="link.label" /></template></nav></footer>
                </section>
                <aside class="details-panel" aria-label="Activity details" aria-live="polite">
                    <div class="details-heading"><h2><span aria-hidden="true">ⓘ</span> Activity Details</h2><span v-if="selected" class="event-badge" :class="eventTone(selected.event.value)">{{ selected.event.label }}</span></div>
                    <template v-if="selected">
                        <div class="record-card"><dl><div><dt>Timestamp</dt><dd>{{ selected.createdAt }}</dd></div><div><dt>Responsible User</dt><dd>{{ selected.actorName }}</dd></div><div><dt>Office Designation</dt><dd><span class="role-badge" :class="{ staff: selected.actorRole !== 'Admin' }">{{ roleLabel(selected.actorRole) }}</span></dd></div><div><dt>Module Area</dt><dd>{{ selected.module.label }}</dd></div></dl><div class="subject"><p>Subject Record:</p><h3>{{ selected.subjectLabel || 'System activity' }}</h3></div></div>
                        <p class="description">{{ selected.description }}</p>
                        <div class="changes-heading"><h3>Recorded changes</h3><span>{{ selected.changeSummary.length }} field updates</span></div>
                        <div v-for="(change, index) in selected.changeSummary" :key="index" class="change-card"><p>{{ change.field }}</p><div class="change-values"><del>{{ change.from }}</del><span aria-hidden="true">→</span><strong>{{ change.to }}</strong></div></div>
                        <p v-if="!selected.changeSummary.length" class="muted-note">No field changes recorded for this activity.</p>
                        <div v-if="selected.metadata.length" class="metadata"><h3>Additional details</h3><dl><div v-for="item in selected.metadata" :key="item.key"><dt>{{ item.key }}</dt><dd>{{ item.value }}</dd></div></dl></div>
                        <Link v-if="selected.recordUrl" :href="selected.recordUrl" class="button record-link">Open related record <span aria-hidden="true">↗</span></Link>
                    </template>
                    <p v-else class="muted-note">Select an activity to view its details.</p>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.audit-page { color: #113b37; background: #f8f7fd; padding: 18px; border-radius: 12px; font-size: 12px; }
svg { width:16px; height:16px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
.button { display:inline-flex; justify-content:center; align-items:center; gap:6px; border:1px solid #eeedf5; border-radius:6px; padding:10px 12px; background:white; font-size:11px; cursor:pointer; text-decoration:none; }
.primary { background:#1b4535; color:white; border-color:#1b4535; font-weight:600; }
button:disabled { opacity:.5; cursor:wait; }
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible { outline:2px solid #29836e; outline-offset:3px; }
.summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
.summary-card { display:flex; align-items:center; justify-content:space-between; gap:12px; background:white; padding:18px 14px; border:1px solid #f0eef8; border-radius:8px; box-shadow:0 1px 2px #30205204; }
.eyebrow { text-transform:uppercase; letter-spacing:.06em; font-size:10px; }
.summary-card h2 { font-size:21px; font-weight:700; margin:9px 0 6px; }
.card-note { color:#087d72; font-size:10px; }
.card-icon { display:grid; place-items:center; width:42px; height:42px; border-radius:7px; flex-shrink:0; }
.card-icon svg { width:21px; height:21px; }
.mint { background:#bcebd6; }.green { background:#94f4c6; }.peach { background:#ffdacb; color:#783815; }
.filter-panel { background:white; padding:11px; border:1px solid #f0eef8; border-radius:8px; margin-bottom:16px; }
.filter-grid { display:grid; grid-template-columns:1.2fr 1fr 1fr 1fr 1fr auto; gap:6px; }
.filter-grid input,.filter-grid select,.filter-control { min-width:0; width:100%; border:0; border-radius:4px; background:#f3f2ff; height:34px; padding:0 10px; font-size:11px; color:#24405d; }
.filter-grid .button { padding:0 12px; }
.filter-control { display:flex; align-items:center; justify-content:space-between; cursor:pointer; }
.search-control { position:relative; min-width:0; }.search-control svg { position:absolute; left:10px; top:10px; width:14px; height:14px; }.search-control input { padding-left:31px; }
.filter-footer { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:12px; font-size:10px; }.filter-footer button { color:#007b77; cursor:pointer; }.count-badge { background:#e5e7ff; border-radius:20px; padding:3px 8px; }.filter-caption { margin-left:5px; color:#64758a; }
.date-controls { display:flex; flex-wrap:wrap; gap:12px; align-items:center; padding-top:12px; }.date-controls input { background:#f3f2ff; border:1px solid #e6e4f1; padding:6px; border-radius:4px; margin-left:5px; }
.activity-layout { display:grid; grid-template-columns:minmax(0,1fr) 315px; gap:16px; align-items:start; }.activity-table-panel { min-width:0; overflow:hidden; background:white; border:1px solid #f0eef8; border-radius:8px; }.table-scroll { overflow-x:auto; }table { width:100%; border-collapse:collapse; text-align:left; }th { background:#f5f5ff; padding:17px 12px; text-transform:uppercase; font-size:9px; letter-spacing:.06em; font-weight:500; white-space:nowrap; }td { padding:24px 12px; font-size:11px; border-bottom:1px solid #faf9fc; }td:first-child { white-space:nowrap; }tr.selected { background:#f1faf5; }tbody tr { cursor:pointer; }tbody tr:hover { background:#f5faf7; }.actor-button { display:flex; align-items:center; gap:5px; white-space:nowrap; font-weight:500; cursor:pointer; text-align:left; }.avatar { display:inline-grid; place-items:center; width:26px; height:26px; border-radius:50%; background:#bdebd6; font-size:9px; flex-shrink:0; }.role-badge { display:inline-block; background:#bdebd6; border-radius:4px; padding:2px 5px; font-size:9px; white-space:nowrap; }.staff { background:#e0e5ff; color:#173c69; }.event-badge { display:inline-block; border-radius:12px; padding:3px 7px; font-size:9px; }.neutral { background:#e0e5ff; color:#294479; }.success { background:#d9f3e4; color:#246743; }.danger { background:#ffe2db; color:#a34435; }
.details-panel { background:white; border:1px solid #f0eef8; border-radius:8px; padding:12px; min-width:0; }.details-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:12px; }.details-heading h2 { font-size:15px; font-weight:700; white-space:nowrap; }.details-heading h2 span { color:#008384; }.record-card,.change-card { background:#f3f2ff; border-radius:5px; padding:10px; }dl { margin:0; }dl > div { display:flex; justify-content:space-between; gap:14px; margin-bottom:9px; }dt { font-size:10px; color:#536580; }dd { margin:0; font-size:10px; text-align:right; overflow-wrap:anywhere; }.subject { margin-top:16px; }.subject p { font-size:10px; color:#536580; }.subject h3 { font-size:13px; font-weight:600; margin:5px 0 0; overflow-wrap:anywhere; }.description { margin:12px 0; font-size:11px; line-height:1.6; color:#536580; }.changes-heading { display:flex; justify-content:space-between; gap:8px; margin:12px 0 6px; }.changes-heading h3,.metadata h3 { text-transform:uppercase; font-size:10px; letter-spacing:.04em; }.changes-heading span { color:#00817d; font-size:10px; }.change-card { margin-bottom:7px; }.change-card > p { color:#536580; font-size:10px; margin-bottom:8px; }.change-values { display:grid; grid-template-columns:minmax(0,1fr) auto minmax(0,1fr); gap:10px; font-size:10px; align-items:center; }.change-values del { color:#85909c; overflow-wrap:anywhere; }.change-values > span { color:#00817d; }.change-values strong { text-align:right; font-weight:500; overflow-wrap:anywhere; }.muted-note { color:#7d8898; line-height:1.6; padding:12px 0; font-size:11px; }.metadata { border-top:1px solid #eeedf5; padding-top:12px; margin-top:12px; }.metadata h3 { margin-bottom:12px; }.record-link { width:100%; margin-top:12px; color:#17634d; }.pagination { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:14px 12px; font-size:10px; color:#788394; }.pagination nav { display:flex; gap:4px; flex-wrap:wrap; }.pagination a,.pagination nav span { padding:5px 8px; border-radius:4px; border:1px solid #eeedf5; }.pagination .current { background:#1b4535; color:white; }.pagination nav span { opacity:.5; }.empty-state { padding:60px 20px; text-align:center; }.empty-state h2 { font-weight:600; }.empty-state p { margin:8px 0 16px; }
@media(min-width:1500px) { .activity-layout { grid-template-columns:minmax(0,1fr) 350px; } }
@media(max-width:1100px) { .filter-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }.activity-layout { grid-template-columns:minmax(0,1fr); } }
@media(max-width:600px) { .audit-page { padding:10px; }.summary-grid { grid-template-columns:1fr; gap:8px; }.summary-card { padding:14px; }.filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }.filter-caption { display:none; } }
@media print { .audit-page { background:white; padding:0; }.filter-panel,.pagination nav,.record-link { display:none; }.activity-layout { display:block; }.table-scroll { overflow:visible; }td { padding:12px 8px; }.details-panel { margin-top:20px; break-inside:avoid; }.summary-card { break-inside:avoid; } }
</style>
