<script lang="ts">
export default {
  emits: ["query-updated", "download"],
  data() {
    return {
      query: ""
    };
  },
  watch: {
    query(newQuery, oldQuery) {
      this.$emit("query-updated", newQuery);
    }
  },
  methods: {
    add() {
    },
    download() {
      this.$emit("download");
    }
  }
}
</script>

<template>
  <nav class="navbar is-fixed-top">
    <div class="navbar-brand">
      <a class="navbar-item has-text-primary">
        <img src="/logo.png" width="25">&nbsp;Autodex
      </a>
    </div>
    <div class="navbar-menu">
      <div class="navbar-start">
      </div>
      <div class="navbar-end">
        <div class="navbar-item">
          <button class="button" @click="add">
            <span class="icon has-text-primary">
              <font-awesome-icon icon="circle-plus" />
            </span>
          </button>
        </div>
        <div class="navbar-item">
          <button class="button" @click="download">
            <span class="icon has-text-primary">
              <font-awesome-icon icon="file-arrow-down" />
            </span>
          </button>
        </div>
        <div class="navbar-item">
          <p class="control has-icons-left">
            <input class="input is-rounded" type="text" placeHolder="Search your contacts" v-model="query"/>
            <span class="icon is-left has-text-primary">
              <font-awesome-icon icon="search"/>
            </span>
          </p>
        </div>
      </div>
    </div>
  </nav>
</template>
