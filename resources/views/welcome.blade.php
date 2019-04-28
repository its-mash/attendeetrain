<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/face-api.js"></script>
  <script src="js/drawing.js"></script>
  <script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
</head>
<body>
    <div style="border:2px solid blue" class="side-by-side">
      <div style="position: relative;border:2px solid red;min-width:640px;min-height:480px" class="margin">
          <video style="" onplay="onPlay()" id="inputVideo" autoplay muted></video>
          <canvas  id="overlay" ></canvas>
      </div>
      <div class="" >
        <img id="cutImage" src="demo.png" alt="dd" style="height:270px;width:270px;object-fit:contain">
      </div>
    </div>
    <div class="side-by-side margin">
      Matric No : <input type="text" name="matricNo">
      Full Name : <input type="text" name="fullName">
      Called As : <input type="text" name="callName">
      <button onclick="snap()">Take pic</button>
      <button onclick="upload()">upload</button>
      Uploaded<span id="counter">0</span>
    </div>
    
</body>

  <script>
        let minFaceSize = 100, takePic=false,imageData="";
        snap = () => {
          takePic=true
          console.log($("input[name=matricNo]").val())
        }
        function upload(){
            if(imageData!=""){
                console.log("uploading")
                $.ajax({
                  type: "POST",
                  url: "addimage",
                  data: {
                    "_token": "{{ csrf_token() }}", 
                    img: imageData,
                    matricNo: $("input[name=matricNo]").val() ,
                    fullName: $("input[name=fullName]").val(),
                    callName: $("input[name=callName]").val()
                  },
                  
                }).done(function(o) {
                  const i=parseInt($('#counter').text());
                  $('#counter').text(i+1);
                  console.log(o); 
                  // If you want the file to be visible in the browser 
                  // - please modify the callback in javascript. All you
                  // need is to return the url to the file, you just saved 
                  // and than put the image in your browser.
                });
            }
        }
        function isFaceDetectionModelLoaded() {
          return !!faceapi.nets.mtcnn.params
        }


        async function run() {
        
            const MODELS = "/model"; // Contains all the weights.
        
            await faceapi.loadMtcnnModel(MODELS)
            // await faceapi.loadFaceLandmarkModel(MODELS)
            // await faceapi.loadFaceRecognitionModel(MODELS)
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} })
            const videoEl = $('#inputVideo').get(0)
            videoEl.srcObject = stream
        }
        
        async function run2() {
            const videoEl = $('#inputVideo').get(0)
            // console.log(takePic)
            if(videoEl.paused || videoEl.ended || !isFaceDetectionModelLoaded())
            return setTimeout(() => onPlay())


            const options = await new faceapi.MtcnnOptions({ minFaceSize })


            const detections = await faceapi.detectSingleFace(videoEl, options);//.withFaceLandmarks().withFaceDescriptor()
            if (detections) {
                // console.log(detections)
                const detectionsForSize = faceapi.resizeResults(detections, { width: videoEl.videoWidth, height: videoEl.videoHeight })
                const canvas = document.getElementById('overlay')
                
                canvas.width = videoEl.videoWidth
                canvas.height = videoEl.videoHeight
                faceapi.drawDetection(canvas, detectionsForSize, { withScore:  true })

                if(takePic){
                    const box=detections.box
                    const regionsToExtract = [
                      new faceapi.Rect(box.x, box.y, box.width, box.height)
                    ]
                    const canvases = await faceapi.extractFaces(videoEl, regionsToExtract)
                    var $image = $("#cutImage")
                    imageData=canvases[0].toDataURL()
                    $image.attr("src",imageData)
                    takePic=false


                }

                // sleep(100)
            }

            setTimeout(() => onPlay())
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