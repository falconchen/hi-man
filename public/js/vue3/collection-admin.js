const app = Vue.createApp({
  delimiters: ['${', '}'],
    data() {
      return {
        modalDisplayObj: {display:"none"},
        title:"",
        slug:"",
        description:"",

        books: [
          {
            title: "Hello Title",
            author: "Falcon Chen",
            isFav:true,
            isMine:true
          },
          {
            title: "We are title",
            author: "Lily",
            isFav:false
          },
          {
            title: "Go with the wind",
            author: "Unkown",
            isFav:true
          },
          {
            title: "Go with the wind",
            author: "Unkown",
            isFav:true
          },
        ],
        
      };
    },
    methods: {
      clickMe() {
        console.log("I have been clicked")
      },
      createCollection(){
        this.modalDisplayObj.display="block";
      },
      closeModal(){
        this.modalDisplayObj.display="none";
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
  app.mount("#app");
  