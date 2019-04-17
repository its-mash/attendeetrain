<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/face-api.js"></script>
  <script src="js/drawing.js"></script>
  <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
</head>
<body>
    <div style="position: relative" class="margin">
        <video onplay="onPlay()" id="inputVideo" autoplay muted></video>
        <canvas id="overlay"></canvas>
    </div>
    <div id="cutImage"></div>
</body>

  <script>
        let minFaceSize = 200
        function isFaceDetectionModelLoaded() {
          return !!faceapi.nets.mtcnn.params
        }


        async function run() {
        
            const MODELS = "/"; // Contains all the weights.
        
            await faceapi.loadMtcnnModel(MODELS)
            

            const stream = await navigator.mediaDevices.getUserMedia({ video: {} })
            const videoEl = $('#inputVideo').get(0)
            videoEl.srcObject = stream
        }
        
        async function run2() {
            const videoEl = $('#inputVideo').get(0)

            if(videoEl.paused || videoEl.ended || !isFaceDetectionModelLoaded())
            return setTimeout(() => onPlay())


            const options = await new faceapi.MtcnnOptions({ minFaceSize })


            const detections = await faceapi.detectSingleFace(videoEl, options)
            if (detections) {
                const detectionsForSize = faceapi.resizeResults(detections, { width: videoEl.videoWidth, height: videoEl.videoHeight })
                const canvas = document.getElementById('overlay')
                
                canvas.width = videoEl.videoWidth
                canvas.height = videoEl.videoHeight
                faceapi.drawDetection(canvas, detectionsForSize, { withScore:  true })


                const box=detections.box
                const regionsToExtract = [
                  new faceapi.Rect(box.x, box.y, box.width, box.height)
                ]
                const canvases = await faceapi.extractFaces(videoEl, regionsToExtract)
                var $image = $("#cutImage");
                 
                var img = document.createElement("img");
                img.src = canvases[0].toDataURL();
                $image.html(img);
                // sleep(100)
            }

            setTimeout(() => onPlay(),100)
        }
        
        async function onPlay() {
            run2()
        }

        $(document).ready(function() {
            
            run()
        })
  </script>
</body>
</html>