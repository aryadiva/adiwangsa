<script>
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('filamentLiveCapture', ({ statePath }) => ({
            streaming: false,
            captured: false,
            preview: null,
            stream: null,
            error: null,
            hasCamera: Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),

            async start() {
                this.error = null

                if (! this.hasCamera) {
                    return
                }

                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                        audio: false,
                    })
                    this.$refs.video.srcObject = this.stream
                    await this.$refs.video.play()
                    this.streaming = true
                } catch (error) {
                    this.hasCamera = false
                    this.error = 'Camera unavailable — use the camera input below.'
                }
            },

            async shoot() {
                const video = this.$refs.video

                if (! video || ! video.videoWidth) {
                    this.error = 'Camera is not ready yet.'

                    return
                }

                const canvas = document.createElement('canvas')
                canvas.width = video.videoWidth
                canvas.height = video.videoHeight
                canvas.getContext('2d').drawImage(video, 0, 0)

                await this.uploadCanvas(canvas)
            },

            fromInput(event) {
                const file = event.target.files?.[0]

                if (! file) {
                    return
                }

                const renamed = new File([file], `capture-${new Date().toISOString()}.jpg`, {
                    type: file.type || 'image/jpeg',
                })

                this.send(renamed)
            },

            uploadCanvas(canvas) {
                canvas.toBlob((blob) => {
                    if (! blob) {
                        this.error = 'Could not process the captured frame.'

                        return
                    }

                    this.send(new File([blob], `capture-${new Date().toISOString()}.jpg`, { type: 'image/jpeg' }))
                }, 'image/jpeg', 0.9)
            },

            async send(file) {
                this.error = null
                this.preview = URL.createObjectURL(file)
                this.captured = true
                this.stopStream()

                try {
                    await this.$wire.upload(statePath, file)
                } catch (error) {
                    this.error = 'Upload failed — try capturing again.'
                    this.captured = false
                    this.preview = null
                }
            },

            retake() {
                this.captured = false
                this.preview = null
                this.$wire.set(statePath, null)
            },

            stopStream() {
                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop())
                    this.stream = null
                }

                this.streaming = false
            },

            destroy() {
                this.stopStream()
            },
        }))
    })
</script>
