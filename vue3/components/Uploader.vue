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
    <div class="form-group">
      <label for="zipFileUpload">Choose Recipe File</label>
      <input type="file" class="form-control-file" id="zipFileUpload" @change="handleFileUpload" accept=".zip" />
    </div>
    <transition name="fade" mode="out-in">
      <div v-if="linkList.length || courseList.length" class="mt-4">
        <div v-if="linkList.length">
          <h3>Links in the ZIP:</h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="link in linkList" :key="link">{{ link }}</li>
          </ul>
        </div>
        <div v-if="courseList.length" class="mt-4">
          <h3>Courses in the ZIP:</h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="course in courseList" :key="course">{{ course }}</li>
          </ul>
        </div>
        <div v-if="simulationList.length" class="mt-4">
          <h3>Simulations in the ZIP:</h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="simulation in simulationList" :key="simulation">{{ simulation }}</li>
          </ul>
        </div>
        <div v-if="questionList.length" class="mt-4">
          <h3>Questions in the ZIP:</h3>
          <ul class="list-group">
            <li class="list-group-item" v-for="question in questionList" :key="question">{{ question }}</li>
          </ul>
        </div>
        <button
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          Install Recipe
        </button>
      </div>
    </transition>
    <div v-if="isInstalling" class="mt-4">
      <h3>Total Progress:</h3>
      <progress :value="totalProgress" max="100"></progress>
      <h3>Current Task Progress:</h3>
      <progress :value="taskProgress" max="100"></progress>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted } from 'vue';
import JSZip from 'jszip';
import { useStore } from 'vuex';

// Reactive state for the list of links and courses
const store = useStore();
const linkList = ref([]);
const courseList = ref([]);
const simulationList = ref([]);
const questionList = ref([]);
let uploadedFile = null;
let uploadedFileName = '';

const isInstalling = ref(false);
const totalProgress = ref(0);
const taskProgress = ref(0);
let progressInterval = null;

const installRecipe = async () => {
  if (uploadedFile) {
    isInstalling.value = true;
    totalProgress.value = 0;
    taskProgress.value = 0;

    // Start polling for progress
    startProgressPolling();

    try {
      const base64File = await convertFileToBase64(uploadedFile);
      await store.dispatch('installRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName
        }
      );
      alert('Recipe installed successfully!');
    } catch (error) {
      console.error('Error installing recipe:', error);
      //alert('Failed to install the recipe.');
    }  finally {
      stopProgressPolling()
      isInstalling.value = false
    }
  } else {
    alert('No file uploaded.');
  }
};

const convertFileToBase64 = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
    reader.readAsDataURL(file);
  });
};

const startProgressPolling = () => {
  //progressInterval = setInterval(getProgress, 1000);
};

const stopProgressPolling = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
    progressInterval = null;
  }
};

const getProgress = async () => {
  try {
    console.log('insindiensinsrfn')
    const response = await store.dispatch('getInstallProgress', {
      filename: uploadedFileName
    });
    totalProgress.value = response.progress * 10
    taskProgress.value = response.subprogress * 10
    console.log('ENDJFIBJABDIHBDIDBIHJBIUB')

  } catch (error) {
    console.error('Error fetching progress:', error);
  }
};

// Function to handle file upload
const handleFileUpload = async (event) => {
  uploadedFile = event.target.files[0];
  if (uploadedFile && uploadedFile.name.endsWith('.zip')) {
    uploadedFileName = uploadedFile.name;
    try {
      const zip = new JSZip();
      const content = await zip.loadAsync(uploadedFile);
      const rootFolder = Object.keys(content.files).filter(file => content.files[file].dir)[0];

      const pluginJsonFile = content.file(`${rootFolder}plugins.json`);
      if (pluginJsonFile) {
        const pluginJsonData = await pluginJsonFile.async("text");
        const pluginData = JSON.parse(pluginJsonData);
        linkList.value = pluginData.links || [];
      }

      // Extract first level course files with .mbz extension
      const courseFolders = Object.keys(content.files)
        .filter(fileName => fileName.startsWith(`${rootFolder}courses/`) && content.files[fileName].dir && fileName.endsWith('.mbz/'))
        .map(fileName => fileName.replace(`${rootFolder}courses/`, ''));
      courseList.value = courseFolders;

      const simulationFiles = Object.keys(content.files)
        .filter(fileName => fileName.startsWith(`${rootFolder}simulations/`) && fileName.endsWith('.csv'))
        .map(fileName => fileName.replace(`${rootFolder}simulations/`, ''));
      simulationList.value = simulationFiles;

      const questionFiles = Object.keys(content.files)
        .filter(fileName => fileName.startsWith(`${rootFolder}questions/`) && fileName.endsWith('.xml'))
        .map(fileName => fileName.replace(`${rootFolder}questions/`, ''));
        questionList.value = questionFiles;

    } catch (error) {
      console.error('Error reading ZIP file:', error);
    }
  } else {
    alert('Please upload a valid ZIP file.');
  }
};

onUnmounted(() => {
  stopProgressPolling();
});

</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
