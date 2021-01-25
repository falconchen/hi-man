const app = Vue.createApp({
  delimiters: ['${', '}'],
    data() {
      return {
        token:"",
        modalIsShown: false,
        hasError:false,
        message:"",
        isMounted:false,
        currentCollection: {          
        },
        collections: [

        ],
        
      };
    },
    methods: {
      
      showCollectionModal(collection){
        
        //console.log(this.token)
        this.currentCollection = (collection != undefined) ? collection :{'collection_id':0}                
        this.modalIsShown = true
        this.hasError = false
        this.message = ''

      },
      hideCollectionModal(){
        this.modalIsShown = false,
        this.currentCollection = {}
      },
      submitCollection(collection){
        
        
        fetch('/api/collections',{
          method: collection.collection_id == 0 ? 'POST' : 'PUT',
          headers:{
            'Content-Type':'application/json',
            'Hi-Token':this.token
          },//注意在body发送json时需要正确设置请求头的 Content-Type

          body: JSON.stringify(collection) 

        }).then(res=> {

          // 此处加入响应状态码判断                             
            
            if(res.ok) {
              this.hideCollectionModal()              
            }
            this.hasError = !res.ok 
            return res.json()
            
        })
        .then(response=>{
          this.message = response.message
          if(!this.hasError) {
            fetch('/api/tokens',{
              method: 'GET'            
          }).then(res=> {
  
              if(res.ok) { // 此处加入响应状态码判断                 
                  return res.json()
              }else{
                  window.location.href='/login'                                
              }
              
          })
          .then(response=>{
            
            this.token = response.data.token
            
            return response
  
          }).then(
            response=>{
              fetch('/api/collections?limit=100',{
                method: 'GET',
                headers:{
                  // 'Content-Type':'application/json',
                  'Hi-Token':response.data.token
                },//注意在body发送json时需要正确设置请求头的 Content-Type                  
      
              }).then(res=> {
      
                // 此处加入响应状态码判断                             
                  this.hasError = !res.ok 
                  return res.json()
                  
              })
              .then(response=>{
                // this.message = response.message
                // console.log(response.message)
                
                this.collections = response.data
              })
              .catch(error=>console.log(error)) 
            }
  
          )
          .catch(error=>console.log(error)) //注意此处只对网络无法连接或者服务器响应超时报错，如果状态码返回404或其他状态码此处不会报错
  


            
          }
          
        })
        .catch(error=>console.log(error)) //注意此处只对网络无法连接或者服务器响应超时报错，如果状态码返回404或其他状态码此处不会报错

      },
     

      deleteCollection(collection){
        
        fetch('/api/collections',{
          method: 'DELETE',
          headers:{
            'Content-Type':'application/json',
            'Hi-Token':this.token
          },//注意在body发送json时需要正确设置请求头的 Content-Type

          body: JSON.stringify(collection) 

        }).then(res=> {

          // 此处加入响应状态码判断                             
            
            if(res.ok) {
              this.hideCollectionModal()              
            }
            this.hasError = !res.ok 
            return res.json()
            
        }).then(res =>{

          
            fetch('/api/collections?limit=100',{
              method: 'GET',
              headers:{
                // 'Content-Type':'application/json',
                'Hi-Token':this.token
              },//注意在body发送json时需要正确设置请求头的 Content-Type                  
    
            }).then(res=> {
    
              // 此处加入响应状态码判断                             
                this.hasError = !res.ok 
                return res.json()
                
            })
            .then(response=>{
              // this.message = response.message
              // console.log(response.message)
              
              this.collections = response.data
            })            
          

        }).catch(error=>console.log(error))



      },
      // editCollectionModal(collection){
      //   console.log(collection)
      // },
      log(data){
        console.log(data)
      }
    },

    created() {

      fetch('/api/tokens',{
            method: 'GET'            
        }).then(res=> {

            if(res.ok) { // 此处加入响应状态码判断                 
                return res.json()
            }else{
                window.location.href='/login'                                
            }
            
        })
        .then(response=>{
          
          this.token = response.data.token
          
          return response

        }).then(
          response=>{
            fetch('/api/collections?limit=100',{
              method: 'GET',
              headers:{
                // 'Content-Type':'application/json',
                'Hi-Token':response.data.token
              },//注意在body发送json时需要正确设置请求头的 Content-Type                  
    
            }).then(res=> {
    
              // 此处加入响应状态码判断                             
                this.hasError = !res.ok 
                return res.json()
                
            })
            .then(response=>{
              // this.message = response.message
              // console.log(response.message)
              
              this.collections = response.data
            })
            .catch(error=>console.log(error)) 
          }

        )
        .catch(error=>console.log(error)) //注意此处只对网络无法连接或者服务器响应超时报错，如果状态码返回404或其他状态码此处不会报错

      
    },
    mounted(){
      this.isMounted = true
    }
  
    // computed: {
    //   filterFavBooks(){
    //     return this.books.filter( book => book.isFav)
    //   },
    //   headerTitle(){
    //     return this.title.length == 0 ? '新文集' : this.title
    //   }
    // }
  
  });




  app.component('collection-modal',{
    props:{ //props是只读的，不要试图改变props,要修改有两种方式
      //https://v3.cn.vuejs.org/guide/component-props.html#%E5%8D%95%E5%90%91%E6%95%B0%E6%8D%AE%E6%B5%81

      collection_id:{
        type:Number,
        default:0,
      },
      title:{
        type:String,
        default:''
      },
      cover:{
        type:String,
        default:''
      },
      slug:{
        type:String,
        default:''
      },
      description:{
        type:String,
        default:''
      },
      message:{
        type:String,
        default:''
      },
      hasError:{
        type:Boolean,
        defalt:false
      }
      
    },          
    data() {
      return {
        collection : {
          collection_id:this.collection_id,
          title:this.title,
          cover:this.cover,
          slug:this.slug,
          description:this.description
        }
      }
    },
    emits:['submit-collection','close-modal','delete-collection'],
    computed: {      
      headerTitle(){
        return this.collection.title == '' ? '新文集' : this.collection.title
      }
    },
    template:`
    <div class="w3-modal" style="display:block">
            
            <form class="w3-modal-content w3-animate-top w3-card-4" style="max-width: 600px;" @submit.prevent="submitCollection" action="" >
                <input type="hidden" name="collection_id" v-model="collection.collection_id">
                <header class="w3-container hi-dark">
                    <span @click="closeModal" class="w3-button w3-display-topright">&times;</span>
                    <h2>{{ headerTitle }}</h2>
                </header>
                <div class="w3-container collection-body">
                
                    <p class="cover w3-grey not-allowed" :style="{backgroundImage:'url('+ collection.cover +')'}">
                        <input type="hidden" name="cover" v-model="collection.cover">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        
                          <slot name="cover-label-slot">
                            <span class="cover-label">Cover</span>
                          </slot>
                        
                    </p>

                    <div class="inputs">
                        <p>
                            <slot name="title-label-slot">
                              <label class="w3-text-black">Title
                                  <span class="w3-text-red">*</span>
                              </label>
                            </slot>
                            

                            <input v-model.trim="collection.title" name="title" class="w3-input w3-border" type="text" required="true" placeholder="文集标题名"/></p>

                        <p>
                            <label class="w3-text-black">Slug</label>
                            <input v-model.trim="collection.slug" name="slug" class="w3-input w3-border" type="text"  placeholder="URL中显示的文集名，非必填，尽量使用英文和数字"/>
                        </p>

                        <p>
                            <label class="w3-text-black">描述</label>
                            <textarea v-model.trim="collection.description" name="description" class="w3-input w3-border" style="resize:none" placeholder="关于文集内容的大概描述，非必填"></textarea>
                        </p>

                    </div>
                </div>

                <footer class="w3-container hi-dark">
                    <p class="w3-left" :class="{'w3-text-red':hasError}" v-if="message">
                      {{message}}
                    </p>
                    <p class="w3-right">

                    
                      <button type="button" class="w3-btn w3-padding  w3-margin-right w3-red" style="width:120px" @click.stop="$emit('delete-collection',collection)" v-if="collection.collection_id >0 ">                    
                        <slot name="button-del-text-slot">
                          <span>Delete &nbsp; ❯</span>                                               
                        </slot>
                      </button>
                     

                        <button type="submit" class="w3-btn w3-padding w3-white" style="width:120px">
                        <slot name="button-text-slot">
                          <span>Submit &nbsp; ❯</span>                          
                         </slot>
                         </button>
                    </p>

                </footer>
            </form>
        </div>
    `,
    methods: {

      closeModal(){
        // this.isShown = false
        this.$emit("close-modal");
      },

      

      submitCollection() {        
        //console.log(this.collection)        
        this.$emit("submit-collection", this.collection);
      }


    }

  }) 

  app.component('create-collection-item', { 

    // props: {
    //   label:{
    //     type: String,
    //     default: 'Create'
    //   },
    
    // },
    emits: ['create-collection'],
    template: `
    <div class="collection-item add" @click="$emit('create-collection')">
                <div class="inner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span><slot>Create</slot></span>
                    

                </div>
      </div>
      `
  })
  


  app.component('collection-item', { 

    props: ['title','description','cover'],
    emits: ['edit-collection','delete-collection'],
    data(){
      return {
        'coverUrl': typeof(this.cover) != undefined ? this.cover :''
      }
    },
    template: `
    <div class="collection-item" 
    :style="{backgroundImage:'url('+ coverUrl ? coverUrl: +')'}" 
    @click="$emit('edit-collection')">
      <div class="inner">
          <h1>{{title}}</h1>
          <span>{{description}}</span>
      </div>
      <span class="w3-button w3-display-topright" @click.stop="$emit('delete-collection')">&times</span>

    </div>
      `
  })

  

  


  app.mount("#app");
  