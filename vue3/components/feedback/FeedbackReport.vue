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
  <div>
    <h2>
      {{ store.state.strings.vueinstallbtn }}
    </h2>
    <div v-for="(messages, type) in feedback" :key="type" style="margin-left: 20px;">
      <h3>{{type}}</h3>
      <ul v-for="(messagestype, key) in messages" :key="key" class="list-group">
        <li v-for="(message, key) in messagestype" :key="key" style="margin-left: 20px; list-style-type: disc;">
          {{ key }}
          <PluginFeedback :message/>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
import PluginFeedback from './PluginFeedback.vue';
import { useStore } from 'vuex'

const store = useStore()
// Define the props the component will receive
const props = defineProps({
  feedback: {
    type: Object,
    required: true,
    default: () => ({
      warning: [],
      error: []
    })
  }
});
</script>

<style scoped>

</style>