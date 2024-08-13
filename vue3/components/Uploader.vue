<!-- // This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Validate if the string does excist.
 *
 * @package     tool_installer
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */ -->

 <template>
  <div class="container mt-4">
    <notifications width="100%"/>
    <div class="nav nav-tabs custom-nav-tabs">
      <a
        class="nav-item nav-link"
        :class="{ active: activeTab === 'install' }"
        @click="activeTab = 'install'"
      >
        Install
      </a>
      <a
        class="nav-item nav-link"
        :class="{ active: activeTab === 'export' }"
        @click="activeTab = 'export'"
      >
        Export
      </a>
    </div>
    <div v-if="activeTab === 'install'">
      <Install />
    </div>
    <div v-if="activeTab === 'export'">
      <Export :courseslist/>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Export from './nav_tabs/Export.vue'
import Install from './nav_tabs/Install.vue'
import { useStore } from 'vuex'

const store = useStore()
const activeTab = ref('install')
const courseslist = ref([])

onMounted(async() => {
  courseslist.value = await store.dispatch('getExportableCourses')
})

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
