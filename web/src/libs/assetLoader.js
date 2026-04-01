export default class AssetsLoader {
    /**
     * assetsLoader 构造函数
     * @param apiUrl 接口地址
     * @param assetRequestDataList 请求的数据信息
     * @param counter 一次请求几个
     * @param loadCallback 每一个请求成功的回调
     * @param errorCallback 每一个请求失败的回调
     */
    constructor (apiUrl, assetRequestDataList, counter, loadCallback, errorCallback) {
        this.loadedCounter = 0;
        this.apiUrl = apiUrl;
        this.chunkSize = 1;
        this.chunkList = [];
        this.chunkIndex = 0;
        this.requestList = [];
        this.requestListTimerId = [];
        this.isAbort = false;
        let total = assetRequestDataList.length;
        if (counter > assetRequestDataList.length) {
            counter = assetRequestDataList.length;
        }
        if (counter <= 0) {
            counter = 1;
        }
        this.chunkSize = Math.ceil(total / counter);
        for (let i = 0; i < this.chunkSize; i++) {
            this.chunkList.push(assetRequestDataList.slice(i * counter, (i + 1) * counter));
        }
        if (this.chunkList.length) {
            this.chunkLoader(apiUrl, this.chunkList[this.chunkIndex], loadCallback, errorCallback);
        }
    }

    /**
     * 检查一批请求是否已结束 成功或失败都算结束
     * @return {boolean}
     */
    checkLoadNumber () {
        return this.loadedCounter === this.chunkList[this.chunkIndex].length;
    }

    /**
     * 加载一小批数据
     * @param apiUrl 接口地址
     * @param chunkList 小批数据的信息
     * @param loadCallback  每一个请求成功的回调
     * @param errorCallback 每一个请求失败的回调
     */
    chunkLoader (apiUrl, chunkList, loadCallback, errorCallback) {
        this.requestListTimerId = [];
        for (let i = 0; i < chunkList.length; i++) {
            let reqData = chunkList[i];
            let id = setTimeout(() => {
                this.assetLoader(apiUrl, reqData, loadCallback, errorCallback);
            }, 100);
            this.requestListTimerId.push(id);
        }
    }

    /**
     * 单个请求
     * @param apiUrl 接口地址 接口返回的是一个或几个图片地址
     * @param assetRequestData 请求参数
     * @param loadCallback 每一个请求成功的回调
     * @param errorCallback 每一个请求失败的回调
     */
    assetLoader (apiUrl, assetRequestData, loadCallback, errorCallback) {
        let request = $.ajax({
            url: apiUrl,
            type: 'post',
            data: assetRequestData,
            timeout: 3 * 10 * 1000,
            success: (res) => {
                if (res.error) {
                    errorCallback && errorCallback(res, assetRequestData);
                    this.completeHandle(request, loadCallback, errorCallback);
                } else {
                    let files = Object.values(res.data);// 适用于图片; 音频,视频的不适用
                    let promiseList = [];
                    files.forEach((file) => {
                        let promise = new Promise((resolve, reject) => {
                            let image = new Image();
                            image.onload = () => {
                                resolve();
                            };
                            image.onerror = () => {
                                resolve();
                            };
                            image.src = file.url;
                        });
                        promiseList.push(promise);
                        Promise.all(promiseList).then(() => {
                            loadCallback && loadCallback(res, assetRequestData);
                            this.completeHandle(request, loadCallback, errorCallback);
                        }).catch(() => {
                            errorCallback && errorCallback(res, assetRequestData);
                            this.completeHandle(request, loadCallback, errorCallback);
                        });
                    });
                }
            },
            error: (res) => {
                errorCallback && errorCallback(res, assetRequestData);
                this.completeHandle(request, loadCallback, errorCallback);
            }
        });
        this.requestList.push(request);
    }
    completeHandle (request, loadCallback, errorCallback) {
        let index = this.requestList.indexOf(request);
        if (~index) {
            this.requestList.splice(index, 1);
        }
        this.loadedCounter++;
        if (this.checkLoadNumber()) {
            this.chunkIndex++;
            if (this.chunkIndex < this.chunkList.length && !this.isAbort) {
                this.loadedCounter = 0;
                this.requestList = [];
                this.chunkLoader(this.apiUrl, this.chunkList[this.chunkIndex], loadCallback, errorCallback);
            } else {
                this.requestList = [];
            }
        }
    }
    /**
     * 取消正在进行中的请求
     */
    abort () {
        this.isAbort = true;
        this.requestListTimerId.forEach(id => {
            clearTimeout(id);
        });
        this.requestList.forEach((request) => {
            request.abort();
        });
    }
};
