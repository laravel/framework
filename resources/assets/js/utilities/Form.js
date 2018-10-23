import Errors from './Errors';

class Form {
    constructor(Data) {
        this.originalData = Data;

        for (let field in Data) {
            this[field] = Data[field];
        }
        this.successData = [];
        this.errors = new Errors();
    }

    data() {
        let data = {};

        for (let property in this.originalData) {
            data[property] = this[property];
        }

        return data;
    }
    
    post(url){
        this.submit('post', url); 
    }
    
    get(url){
        this.submit('get', url); 
    }
    
    submit(requestType, url) {
        return new Promise((resolve, reject) => {

            axios[requestType](url, this.data())
                    .then(response => {
                        this.onSuccess(response.data);

                        resolve(response.data);
                    })
                    .catch(error => {
                        this.onFail(error.response.data);
                
                        reject(error.response.data);
                    });
        });
        
    }

    onSuccess(response) {
        this.successData.push(response);
        this.reset();
    }

    onFail(errors) {
        this.errors.record(errors);
    }

    reset() {
        for (let field in this.originalData) {
            this[field] = '';
        }
        
        this.errors.clear();
    }

}

export default Form;