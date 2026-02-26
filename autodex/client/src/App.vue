<script lang="ts">
import { Contact, Address, Organization } from '../../lib/types';
import ContactGrid from './components/ContactGrid.vue';
import AutodexNavbar from './components/AutodexNavbar.vue';
import appPackage from '../../package.json';

const API_URL = 'http://localhost:8888';

export default {
  data() {
    return {
      contacts: [] as Contact[],
      columns: ['contact', 'phone', 'email', 'organization', 'address1', 'city', 'statecode', 'postcode1', 'postcode2'],
      searchQuery: '',
    };
  },

  components: {
    AutodexNavbar,
    ContactGrid
  },

  created() {
    this.fetchData();
  },

  computed: {
    appVersion() {
      return appPackage.version;
    },
  },

  methods: {
    async download() {
      const csv: string[] = [];
      csv.push("CONTACT,PHONE,EMAIL,ORGANIZATION,ADDRESS1,ADDRESS2,CITY,STATECODE,POSTCODE1,POSTCODE2,COUNTRYCODE");
      this.contacts.forEach(org => {
        const line = `"${org.contact}","${org.phone}","${org.email}", "${org.organization}","${org.address1}","${org.address2}","${org.city}","${org.statecode}","${org.postcode1}","${org.postcode2}","${org.countrycode}"`;
        csv.push(line);
      });
      const text = csv.join("\n");

      const element = document.createElement("a");
      element.setAttribute("href", "data:text/csv;charset=utf-8," + encodeURIComponent(text));
      element.setAttribute("download", "contacts.csv");

      element.style.display = "none";
      document.body.appendChild(element);

      element.click();

      document.body.removeChild(element);
    },
    async fetchData() {
      const url = `${API_URL}/contacts`;
      this.contacts = await (await fetch(url)).json();
    },
    async trash(entry: Contact) {
      const url = `${API_URL}/contacts/${entry.cid}`;
      const options = { method: "DELETE" };
      fetch(url, options);

      const n = this.contacts.indexOf(entry);
      if (n) {
        this.contacts.splice(n, 1);
      }
    },
    async updateQuery(query: string) {
      this.searchQuery = query;
    }
  },
};
</script>

<template>
  <div>
    <AutodexNavbar @query-updated="updateQuery" @download="download" />
    <div class="level">
      <ContactGrid :data="contacts" :columns="columns" :filter-key="searchQuery" @trash-contact="trash" @download="download" />
    </div>
    <footer class="footer">
      <hr>
      Autodex v{{ appVersion }}<br/>
      &copy; 2022 <a href="https://metatooth.com">Metatooth LLC</a>
    </footer>
  </div>
</template>

<style lang="scss">
@import "../assets/main.scss";
</style>
