<template>
  <div :class="{ 'loading-cursor': isInstalling }" class="container mt-4">
    <div v-if="nextstep && !finished.status">
      <p>{{ store.state.strings.vuenextstep }}</p>
      {{ isInstalling }}
        <button
          v-if="nextstep"
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          {{ store.state.strings.vuenextstepbtn }}
        </button>
    </div>
    <div  v-if="finished.status">
      <p>{{ store.state.strings.vuefinishedrecipe }}</p>
    </div>
    <StepCounter :finished/>
    <div
      class="dropzone"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      :class="{ 'is-dragging': isDragging }"
    >
      <p v-if="!uploadedFileName">{{ store.state.strings.vuechooserecipe }}</p>
      <p v-else>{{ uploadedFileName }}</p>
      <p>
        <input
          type="file"
          class="form-control-file"
          id="zipFileUpload"
          @change="handleFileUpload"
          accept=".zip"
          ref="fileInput"
          :disabled="nextstep || isInstalling"
          hidden
        />
        <label for="zipFileUpload" class="btn btn-primary mt-4">
          {{ store.state.strings.uploadbuttontext }}
        </label>
      </p>
      <p>
        <input
          type="file"
          class="form-control-file"
          id="zipFileUploadWithout"
          @change="handleFileUploadWithout"
          accept=".zip"
          ref="fileInputWithout"
          :disabled="nextstep || isInstalling"
          hidden
        />
        <label for="zipFileUploadWithout" class="btn btn-primary mt-4">
          checkRecipeWithout
        </label>
      </p>
    </div>
    <transition name="fade">
      <div v-if="isInstalling" class="waiting-screen mt-4">
        <div class="spinner"></div>
        <p>
          {{ store.state.strings.vuewaitingtext }}
        </p>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <CheckFeedbackReport :feedback/>
        <button
          v-if="!nextstep"
          class="btn btn-primary mt-4"
          @click="installRecipe"
          :disabled="isInstalling"
        >
          {{ store.state.strings.vueinstallbtn }}
        </button>
      </div>
    </transition>
    <transition name="fade">
      <div v-if="!uploadedFileName && Object.values(feedback).length > 0" class="mt-4">
        <FeedbackReport :feedback/>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';
import { notify } from "@kyvg/vue3-notification"
import FeedbackReport from '../feedback/FeedbackReport.vue';
import CheckFeedbackReport from '../feedback/CheckFeedbackReport.vue';
import StepCounter from '../feedback/StepCounter.vue'

// Reactive state for the list of links and courses
const store = useStore();
const feedback = ref([]);
const finished = ref({ status: false });
const checkedOptionalPlugins = ref([]);
let uploadedFile = ref(null);
let uploadedFileName = ref('');
const fileInput = ref(null);
const fileInputWithout = ref(null);
let nextstep = ref(false);

const isInstalling = ref(false);
const isDragging = ref(false);

const totalProgress = ref(0);
const taskProgress = ref(0);

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file && file.name.endsWith('.zip')) {
    processFile(file);
  } else {
    notify({
      title: "Invalid File",
      text: "Please upload a valid .zip file.",
      type: "error"
    });
  }
};

const processFile = async (file) => {
  feedback.value = [];
  isInstalling.value = true;
  uploadedFile.value = file;
  uploadedFileName.value = file.name;
  checkRecipe(file)

};

const checkRecipe = async (file) => {
  console.log(file)
  console.log('entering function', feedback.value);
  console.log('feedback:', feedback.value);
  console.log('finished:', finished.value);
  console.log('uploadedFileName:', uploadedFileName.value);
  console.log('isInstalling:', isInstalling.value);
  isInstalling.value = true;
  try {
    console.log('before convertFileToBase64');
    console.log('feedback:', feedback.value);
    console.log('finished:', finished.value);
    console.log('uploadedFileName:', uploadedFileName.value);
    const base64File = await convertFileToBase64(file);
    console.log('before dispatch checkRecipe');
    console.log('feedback:', feedback.value);
    console.log('finished:', finished.value);
    console.log('uploadedFileName:', uploadedFileName.value);
    const response = await store.dispatch('checkRecipe', {
      uploadedFile: base64File,
      filename: uploadedFileName.value,
    });
    console.log('after dispatch checkRecipe');
    console.log('feedback:', response);
    console.log('feedback:', feedback.value);
    console.log('finished:', finished.value);
    console.log('uploadedFileName:', uploadedFileName.value);
    const responseparsed = JSON.parse(response.feedback)
    feedback.value = responseparsed.feedback
    finished.value = responseparsed.finished
  } catch (error) {
    console.log('error')
    console.log(error)
    notify({
      title: store.state.strings.error,
      text: store.state.strings.error_description,
      type: 'error'
    });
  } finally {
    isInstalling.value = false;
  }
}

const checkRecipeWithout = async (file) => {
  console.log(file)
  isInstalling.value = true;
  const base64File = await convertFileToBase64(file);
  console.log(base64File)
  isInstalling.value = false;
}

const installRecipe = async () => {
  if (uploadedFile.value) {
    feedback.value = []
    isInstalling.value = true;
    totalProgress.value = 0;
    taskProgress.value = 0;
    try {
      const base64File = await convertFileToBase64(uploadedFile.value);
      const selectedPlugins = JSON.stringify(checkedOptionalPlugins.value);
      const response = await store.dispatch('installRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName.value,
          selectedOptionalPlugins: selectedPlugins
        }
      );
      const responseparsed = JSON.parse(response);
      feedback.value = responseparsed.feedback || []
      finished.value = responseparsed.finished || { status: false }
      if (!finished.value.status) {
        nextstep.value  = true
      }

      if (feedback.value.status == 0) {
        notify({
          title: store.state.strings.success,
          text: store.state.strings.success_description,
          type: 'success'
        });
      } else if (feedback.value.status == 1) {
        notify({
          title: store.state.strings.warning,
          text: store.state.strings.warning_description,
          type: 'warn'
        });
      } else if (feedback.value.status == 2) {
        notify({
          title: store.state.strings.error,
          text: store.state.strings.error_description,
          type: 'error'
        });
      }
    } catch (error) {
      notify({
        title: store.state.strings.error,
        text: store.state.strings.error_description,
        type: 'error'
      });
    }  finally {
      if (finished.value.status) {
        nextstep.value  = false
        uploadedFile.value = null
        uploadedFileName.value = ''
        if (fileInput.value) {
          fileInput.value.value = '';
        }
      }
      isInstalling.value = false
    }
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

// Function to handle file upload
const handleFileUpload = async (event) => {
  console.log(event)
  feedback.value = []
  isInstalling.value = true;
  uploadedFile.value = event.target.files[0];
  if (uploadedFile.value && uploadedFile.value.name.endsWith('.zip')) {
    uploadedFileName.value = uploadedFile.value.name;
    checkRecipe(uploadedFile.value)
  } else {
    uploadedFileName.value = '';
  }
};

const handleFileUploadWithout = async (event) => {
  console.log(event)
  feedback.value = []
  isInstalling.value = true;
  uploadedFile.value = event.target.files[0];
  if (uploadedFile.value && uploadedFile.value.name.endsWith('.zip')) {
    uploadedFileName.value = uploadedFile.value.name;
    checkRecipeWithout(uploadedFile.value)
  } else {
    uploadedFileName.value = '';
  }
};

</script>

<style scoped>
.dropzone {
  border-radius: 1rem;
  border: 2px dashed #3498db;
  padding: 1rem;
  text-align: center;
  margin-top: 1rem;
  position: relative;
  cursor: pointer;
}

.dropzone.is-dragging {
  background-color: #f0f8ff;
}

.upload-button {
  cursor: pointer;
  color: #3498db;
  display: inline-block;
  margin-top: 1rem;
}
.waiting-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-left-color: #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.waiting-screen p {
  margin-top: 1rem;
  font-size: 1.2rem;
  color: #333;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.list-group{
  margin: 1rem;
}
.loading-cursor {
  cursor: progress;
}
</style>
