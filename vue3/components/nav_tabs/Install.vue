<template>
  <div :class="{ 'loading-cursor': isInstalling }" class="container mt-4">
    <div v-if="status == 3">
      <p>{{ store.state.strings.vuemanualupdate }}</p>
        <button
          class="btn btn-primary mt-4"
          @click="updateMoodle"
        >
          {{ store.state.strings.vuemanualupdatebtn }}
        </button>
    </div>
    <div v-else-if="nextstep && !finished.status">
      <p>{{ store.state.strings.vuenextstep }}</p>
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
    <StepCounter
      v-if="finished.maxstep"
      :finished
    />
    <ErrorMsg
      v-if="errormsg"
      :errormsg
    />
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
          hidden
        />
        <label
          for="zipFileUpload"
          class="btn btn-primary mt-4"
          :class="{ 'btn-disabled': isInstalling }"
        >
          {{ store.state.strings.uploadbuttontext }}
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
      <div v-if="uploadedFileName"
        class="mt-4"
      >
        <div v-if="feedback.error"
          class="mt-4"
        >
          <div v-if="feedback.error" class="error-message mt-4">
            <i class="fas fa-exclamation-circle"></i> {{ feedback.error[0] }}
          </div>
        </div>
        <div v-else-if="
          feedback &&
          Object.values(feedback).length > 0"
          class="mt-4"
        >
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
import ErrorMsg from '../feedback/ErrorMsg.vue'

// Reactive state for the list of links and courses
const store = useStore();
const feedback = ref([]);
const status = ref(null);
const finished = ref({ status: false });
const checkedOptionalPlugins = ref([]);
let uploadedFile = ref(null);
let uploadedFileName = ref('');
const fileInput = ref(null);
let nextstep = ref(false);
let errormsg = ref(null);

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
  isInstalling.value = true;
  try {
    errormsg.value = null
    const base64File = await convertFileToBase64(file);
    const response = await store.dispatch('checkRecipe', {
      uploadedFile: base64File,
      filename: uploadedFileName.value,
    });
    const responseparsed = JSON.parse(response.feedback)
    feedback.value = responseparsed.feedback
    finished.value = responseparsed.finished
  } catch (error) {
    console.log('error')
    console.log(error)
    errormsg.value = error
    notify({
      title: store.state.strings.error,
      text: store.state.strings.error_description,
      type: 'error'
    });
  } finally {
    isInstalling.value = false;
  }
}

const updateMoodle = () => {
  status.value = 0
  const moodleUrl = store.state.wwwroot + "/admin";
  window.open(moodleUrl, "_blank");
}

const installRecipe = async () => {
  if (uploadedFile.value) {
    feedback.value = []
    isInstalling.value = true;
    totalProgress.value = 0;
    taskProgress.value = 0;
    try {
      errormsg.value = null
      const base64File = await convertFileToBase64(uploadedFile.value);
      const selectedPlugins = JSON.stringify(checkedOptionalPlugins.value);
      const response = await store.dispatch('installRecipe',
        {
          uploadedFile: base64File,
          filename: uploadedFileName.value,
          selectedOptionalPlugins: selectedPlugins
        }
      );
      feedback.value = JSON.parse(response.feedback) || []
      finished.value = JSON.parse(response.finished) || { status: false }
      status.value = check_update_status(response.status, feedback.value)

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
      errormsg.value = error
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

const check_update_status = (status, feedback) => {
  if (status != 3) {
    return
  }
  console.log('check_update_status')
  console.log(status)
  console.log(feedback)
  outerLoop: for (const feedbacktypes of feedback) {
    for (const feedbackcomponent of feedbacktypes) {
  console.log(feedbacktypes)

      if ('success' in feedbackcomponent) {
        status = 2;
        break outerLoop; // Exits both loops
      }
    }
  }
}

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

</script>

<style scoped>
.btn-disabled {
  cursor: not-allowed;
  opacity: 0.6;
  pointer-events: none;
}

.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 1rem;
  border-radius: 0.25rem;
  display: flex;
  align-items: center;
  font-weight: bold;
}

.error-message i {
  color: #721c24;
  margin-right: 0.5rem;
}
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
