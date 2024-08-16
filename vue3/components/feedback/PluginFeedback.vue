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
  <ul>
    <!-- Display Warnings in Dark Orange -->
    <li v-for="warning in message.warning" :key="warning" class="warning-text">
      Warning: {{ warning }}
    </li>
    <!-- Display Errors in Red and Bold -->
    <li v-for="error in message.error" :key="error" class="error-text">
      Error: {{ error }}
    </li>
    <li v-for="success in message.success" :key="success" class="success-text">
      Success: {{ success }}
    </li>
  </ul>
</template>

<script setup>
import { defineProps } from 'vue';

// Define the props the component will receive
const props = defineProps({
  message: {
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
.warning-text {
  color: darkorange;
  margin-left: 20px;
}

.error-text {
  color: red;
  font-weight: bold;
  margin-left: 20px;
}

.success-text {
  color: rgb(22, 193, 22);
  margin-left: 20px;
}
</style>