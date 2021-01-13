const app = Vue.createApp({
  delimiters: ['${', '}'],
    data() {
      return {
        modalIsShown: false,
        currentCollection: {          
        },
        collections: [
          {
            id:123,
            title: "Hello Title",            
            cover:'',
            slug:"hello-title",
            description:"nothing to say"
          },
          {
            id:13,
            title: "Go2",            
            cover:'',
            slug:"hello-gogog",
            description:"nothsd"
          },
          
        ],
        
      };
    },
    methods: {
      
      showCollectionModal(collection){
        
        this.currentCollection = collection ? collection :{}                
        this.modalIsShown = true

      },
      hideCollectionModal(){
        this.modalIsShown = false
      },
      // editCollectionModal(collection){
      //   console.log(collection)
      // },
      log(data){
        console.log(data)
      }
    },
  
    computed: {
      filterFavBooks(){
        return this.books.filter( book => book.isFav)
      },
      headerTitle(){
        return this.title.length == 0 ? '新文集' : this.title
      }
    }
  
  });

  app.component('collection-modal',{
    props:{ //props是只读的，不要试图改变props,要修改有两种方式
      //https://v3.cn.vuejs.org/guide/component-props.html#%E5%8D%95%E5%90%91%E6%95%B0%E6%8D%AE%E6%B5%81

      id:{
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
      }
    },          
    data() {
      return {
        collection : {
          id:this.id,
          title:this.title,
          cover:this.cover,
          slug:this.slug,
          description:this.description
        }
      }
    },
    emits:['submit-collection','close-modal'],
    computed: {      
      headerTitle(){
        return this.collection.title == '' ? '新文集' : this.collection.title
      }
    },
    template:`
    <div class="w3-modal" style="display:block">
          
            <form class="w3-modal-content w3-animate-top w3-card-4" style="max-width: 600px;" @submit.prevent="submitCollection" action="" >
                <header class="w3-container hi-dark">
                    <span @click="closeModal" class="w3-button w3-display-topright">&times;</span>
                    <h2>{{ headerTitle }}</h2>
                </header>
                <div class="w3-container collection-body">
                
                    <p class="cover w3-bg-grey" :style="{backgroundImage:'url('+ cover +')'}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>封面</span>
                    </p>

                    <div class="inputs">
                        <p>

                            <label class="w3-text-black">标题
                                <span class="w3-text-red">*</span>
                            </label>

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
                    <p class="w3-right">
                        <button type="submit" class="w3-btn w3-padding w3-white" style="width:120px">创建 &nbsp; ❯</button>
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

    props: {
      label:{
        type: String,
        default: 'Create'
      },
    
    },
    emits: ['create-collection'],
    template: `
    <div class="collection-item add" @click="$emit('create-collection')">
                <div class="inner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>{{ label }}</span>
                </div>
      </div>
      `
  })


  app.component('collection-item', { 

    props: ['title','author'],
    emits: ['edit-collection'],
    template: `
    <div class="collection-item" @click="$emit('edit-collection',{title:title,author:author})">
      <div class="inner">
          <strong>{{title}}</strong>
          <span>{{author}}</span>
      </div>
    </div>
      `
  })

  app.mount("#app");
  