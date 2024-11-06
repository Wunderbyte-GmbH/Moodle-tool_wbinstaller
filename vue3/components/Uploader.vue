<template>
  <div class="container mt-4">
    <notifications width="100%"/>
    <div class="nav nav-tabs custom-nav-tabs">
      <a
        class="nav-item nav-link"
        :class="{ active: activeTab === 'install' }"
        @click="activeTab = 'install'"
      >
        {{ store.state.strings.vueinstall }}
      </a>
    </div>
    <div v-if="activeTab === 'install'">
      <Install />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Install from './nav_tabs/Install.vue'
import { useStore } from 'vuex'

const store = useStore()
const activeTab = ref('install')

</script>

<style scoped>
.custom-nav-tabs {
  border-bottom: 2px solid #ddd;
}

.nav-item {
  cursor: pointer;
  padding: 10px 20px;
  margin-right: 10px;
  color: #007bff;
  border: 1px solid transparent;
  border-radius: 4px 4px 0 0;
  transition: background-color 0.3s, border-color 0.3s, color 0.3s;
}

.nav-item:hover {
  background-color: #f8f9fa;
  color: #0056b3;
  border-color: #ddd;
}

.nav-link.active {
  background-color: #007bffba;
  color: #fff !important;
  font-weight: bold;
  border-color: #007bff;
}

.nav-link {
  text-decoration: none;
}
</style>
