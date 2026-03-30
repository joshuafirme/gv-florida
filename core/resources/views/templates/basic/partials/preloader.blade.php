 @php
    $contents = getContent('banner.content', true);
@endphp
 <div class="loader-wrapper">
     <div class="loader">

         <div class="road"></div>

         <div class="bus">
             <!-- Replace src with your actual image path -->
             <img src="{{ getImage('assets/images/frontend/banner/' . @$contents->data_values->animation_image, '200x69') }}"
                 alt="bus">

             <div class="wheel front"></div>
             <div class="wheel back"></div>

             <div class="smoke">
                 <span></span>
                 <span></span>
                 <span></span>
             </div>
         </div>

     </div>
 </div>
