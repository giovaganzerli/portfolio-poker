import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { LoadingController } from '@ionic/angular';
import { NativeStorage } from '@ionic-native/native-storage/ngx';

@Injectable({
  providedIn: 'root'
})
export class ChiamateApiService {
  private server_url: string;
  private header: HttpHeaders;
  private spinner: any;

  constructor(
      private http: HttpClient,
      public loadingController: LoadingController,
      private localStorage: NativeStorage
  ) {
    this.server_url = 'http://devpoker.tlco.info/wp-json/poker-plugin/v1/';

    this.header = new HttpHeaders({
      'Content-Type':  'application/json'
    });

    this.localStorage.getItem('user').then(
        (success) => {
          console.log(success);
          this.header = this.header.set('Authorization', 'Bearer ' + success.tokens.session);
          console.log(this.header);
        },
        error => console.error(error)
    );

    this.loadingController.create().then((res)=>{
      this.spinner = res;
    });
  }

  public userLogin__post(params, successHandler, errorHandler){
    this.spinner.present();
    this.http.post(this.server_url + 'auth/token', params, {headers: this.header})
        .subscribe(
            (data: any[])=>{
              this.spinner.dismiss();
              successHandler(data);
              },
            (error) => {
              this.spinner.dismiss();
              errorHandler(error);
            });
  }

  public rfreshToken__post(params, successHandler, errorHandler){
    this.spinner.present();
    //TODO nel params devo inserire il parametro 'token' con il valore dell'auth token preso dal local storage
    this.http.post(this.server_url + 'auth/token/refresh', params, {headers: this.header})
        .subscribe(
            (data: any[])=>{
              this.spinner.dismiss();
              successHandler(data);
            },
            (error) => {
              this.spinner.dismiss();
              errorHandler(error);
            });
  }

  public chiamataTest__get(endpoint, params = {}, successHandler, errorHandler){
    // const url = `${this.server_url}${endpoint}`;
    this.http.get(this.server_url + endpoint, {headers: this.header,params: params})
        .subscribe(
            (data: any[])=>{ successHandler(data); },
            (error) => { errorHandler(error); });
  }

  public sendPostRequest__post(params = {}, successHandler, errorHandler){
    this.http.post('http://devpoker.tlco.info/wp-json/acf/v3/scadenze/4395', params, {headers: this.header})
        .subscribe(
            (data: any[])=>{ successHandler(data); },
            (error) => { errorHandler(error); });
  }

}
