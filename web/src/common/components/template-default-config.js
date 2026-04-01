import cloneDeep from 'lodash.clonedeep';
import formGroupConf from './template-module-management/form-group-default-conf';
import singleInputConf from './template-module-management/single-input-default-conf';
import layoutConf from './template-module-management/layout-default-conf';
import multiInput from './template-module-management/multi-input-default-conf';
import showText from './template-module-management/show-text-default-conf';
import formRadio from './template-module-management/form-radio-default-conf';
import formCheckbox from './template-module-management/form-checkbox-default-conf';
import formSelect from './template-module-management/form-select-default-conf';
import formUpload from './template-module-management/form-upload-default-conf';
import showImg from './template-module-management/show-img-default-conf';
import keyPoint from './template-module-management/key-point-default-conf';
import keyPointGroup from './template-module-management/key-point-group-default-conf';
import AudioPlaceholder from './template-module-management/audio-placeholder-default-conf';
import VideoPlaceholder from './template-module-management/video-placeholder-default-conf';
import ImagePlaceholder from './template-module-management/image-placeholder-default-conf';
import TextPlaceHolder from './template-module-management/text-placeholder-default-conf';
import Ocr from './template-module-management/ocr-default-conf';
import Tag from './template-module-management/tag-default-conf';
import DataIsValid from './template-module-management/data-is-valid-default-conf';
import ImageLabelTool from './template-module-management/image-label-tool-default-conf';

const defaultConfig = {
    'form-group': formGroupConf,
    'single-input': singleInputConf,
    'multi-input': multiInput,
    'show-text': showText,
    'form-radio': formRadio,
    'form-checkbox': formCheckbox,
    'form-select': formSelect,
    'form-upload': formUpload,
    'show-img': showImg,
    //ocr: Ocr,
    tag: Tag,
    'key-point': keyPoint,
    'key-point-group': keyPointGroup,
    'task-file-placeholder': ImagePlaceholder,
    // 'external-link': '',
    'audio-file-placeholder': AudioPlaceholder,
    'video-file-placeholder': VideoPlaceholder,
    'text-file-placeholder': TextPlaceHolder,
    layout: layoutConf,
    'data-is-valid': DataIsValid,
    'image-label-tool': ImageLabelTool,
};

function getDefaultConfig (type, subType = 'image') {
    if (defaultConfig[type]) {
        if (type === 'form-upload') {
            return cloneDeep(defaultConfig[type][subType]);
        } else {
            return cloneDeep(defaultConfig[type]);
        }
    } else {
        return {};
    }
}

export default getDefaultConfig;
