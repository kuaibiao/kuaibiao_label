import EventEmitter from 'eventemitter3';
import api from '../api/index';
import PQueue from 'p-queue';
// import isEqual from 'lodash.isequal';
// import find from 'lodash.find';
export function saveAsFile (json, index, data, option = {}) {
    return new Promise((resolve, reject) => {
        let file = new File([json], `${option.dataId}_${data[index].index}.json`, {type: 'application/json'});
        let formData = new FormData();
        formData.append('file', file);
        formData.append('project_id', option.project_id);
        formData.append('type', 'temporaryStorage');
        formData.append('access_token', option.access_token);
        $.ajax({
            url: api.upload.projectFiles,
            type: 'post',
            processData: false,
            contentType: false,
            data: formData,
            success: (res) => {
                if (res.error) {
                    data[index].indexs = '';
                } else {
                    data[index].indexs = api.apiBase + '/' + res.data.url;
                }
                resolve();
            },
            error: () => {
                data[index].indexs = '';
                resolve();
            }
        });
    });
}
export default class AutoSave extends EventEmitter {
    constructor (options) {
        super();
        this.options = options || {};
        this.saveType = options.saveType;
        this.timewait = options.timewait || 2 * 60 * 1000; // 保存时间间隔
        this.saveUrl = options.saveUrl;
        this.request = void 0;
        this.requestData = options.data;
        this.timerId = void 0;
        this.saveTime = void 0;
        this.isSaving = false;
        this.start();
    }

    start () {
        this.timerId = setInterval(() => {
            this._save();
        }, this.timewait);
    }

    _save () {
        let result = void 0;
        this.emit('beforeSave');
        if (typeof this.requestData !== 'undefined') {
            if (typeof this.requestData === 'function') {
                result = this.requestData();
            } else {
                result = this.requestData;
            }
        }
        // 正在保存或者结果为空
        if (this.isSaving || typeof result === 'undefined') {
            this.emit('save');
            return false;
        }
        if (this.saveType === 'file') {
            let data = result.work_result.data;
            let queue = new PQueue({
                concurrency: 2,
            });
            data.forEach((item, index) => {
                queue.add(() => {
                    return saveAsFile(JSON.stringify(item.indexs), index, data, this.options);
                });
            });
            queue.start();
            let onIdle = queue.onIdle();
            onIdle.then(() => {
                if (data.some((item) => {
                    return item.indexs === '';
                })) {
                    this.emit('error');
                    return;
                }
                this.isSaving = true;
                result.work_result = JSON.stringify(result.work_result);
                this.__save(result);
            });
        } else {
            this.isSaving = true;
            this.__save(result);
        }
    }
    __save (data) {
        this.request = $.ajax({
            url: this.saveUrl,
            type: 'post',
            data: data,
            timeout: 0,
            success: (res) => {
                this.isSaving = false;
                if (res.error) {
                    this.emit('error');
                } else {
                    this.emit('save');
                }
                this.request = null;
            },
            error: () => {
                this.request = null;
                this.isSaving = false;
                this.emit('error');
            }
        });
    }
    save () {
        this.stop();
        this._save();
        this.start();
    }

    stop () {
        if (this.timerId) {
            clearInterval(this.timerId);
        }
    }
    destroy () {
        this.stop();
        this.removeAllListeners();
        this.options = void 0;
        this.requestData = void 0;
        if (this.request) {
            this.request.abort();
            this.request = null;
        }
    }
}
