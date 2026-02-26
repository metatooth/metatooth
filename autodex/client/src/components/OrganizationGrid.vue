<script lang="ts">
import { PropType } from 'vue';
import { Organization } from '../../../lib/types';

export default {
  props: {
    data: { type: Array as PropType<Organization[]>, required: true },
    columns: { type: Array as PropType<string[]>, required: true },
    filterKey: { type: String, default: '' },
  },
  data() {
    const obj: Record<string, any> = {};
    return {
      sortKey: this.columns[0],
      sortOrders: this.columns.reduce((o, key) => ((o[key] = 1), o), obj),
    };
  },
  computed: {
    filtered() {
      const { sortKey } = this;
      const filterKey = this.filterKey && this.filterKey.toLowerCase();
      const order = this.sortOrders[sortKey] || 1;
      let data = this.data || [];
      if (filterKey) {
        data = data.filter((row) => {
          return Object.keys(row).some((key) => {
            return String(row[key]).toLowerCase().indexOf(filterKey) > -1;
          });
        });
      }
      if (sortKey) {
        data = data.slice().sort((a: Record<string, any>, b: Record<string, any>) => {
          a = a[sortKey];
          b = b[sortKey];
          return (a === b ? 0 : a > b ? 1 : -1) * order;
        });
      }
      return data;
    },
  },
  methods: {
    capitalize(str: string) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
    sortBy(key: string) {
      this.sortKey = key;
      this.sortOrders[key] = this.sortOrders[key] * -1;
    },
    trash(entry: Organization) {
      const name = entry.organization || entry.id;
      if (confirm(`OK to delete ${name}?`)) {
        this.$emit("trash-contact", entry);
      }
    }
  },
};
</script>

<template>
  <table class="table is-striped" v-if="filtered.length">
    <thead>
      <tr>
        <th v-for="key in columns"
            :key="key"
            @click="sortBy(key)"
            :class="{ active: sortKey == key }">
          {{ capitalize(key) }}
          <span class="arrow" :class="sortOrders[key] > 0 ? 'asc' : 'dsc'">
          </span>
        </th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="entry in filtered" :key="entry.id">
        <td v-for="key in columns" :key="key">
          {{ entry[key] }}
        </td>
        <td>
          <button class="button" @click="trash(entry)" >
            <span class="icon">
              <font-awesome-icon icon="trash" />
            </span>
          </button>
        </td>
      </tr>
    </tbody>
  </table>
  <p v-else>No matches found.</p>
</template>

<style>
</style>
