# kuaibiao_label

支持图片、文本、音频、视频、3D点云的全类型一站式数据标注解决方案。

## 特性

- **多类型支持**：图像标注、文本标注、音频标注、视频标注、3D点云标注
- **标注工具**：矩形框、多边形、描点、画线、OCR等多种标注工具
- **工作流**：支持执行、审核、质检等工序的作业流转
- **拖拽式配置**：可视化模板配置，快速创建标注项目
- **多角色**：管理员和作业员两种角色，灵活管理

## 技术栈

| 分类 | 技术 |
|-----|------|
| 后端 | PHP 7.4+ / Yii2 Framework |
| 前端 | Vue.js 2 / iview / webpack |
| 数据库 | MySQL |
| 缓存 | Redis |
| 视频处理 | FFmpeg |

## 环境要求

- PHP >= 7.4.0
- MySQL >= 5.7
- Redis
- FFmpeg (可选，用于音视频处理)

## 目录结构

```
kuaibiao_label/
├── api/                  # 后端 API (Yii2)
│   ├── api/              # API 应用
│   ├── common/           # 公共模块
│   └── console/          # 控制台命令
├── web/                  # 前端 (Vue.js)
│   ├── src/              # 源代码
│   └── build/            # 构建配置
└── docs/                 # 详细文档
    └── 数据标注系统介绍-lite-v1.md
```

## 快速开始

### 后端配置

1. 安装依赖：
```bash
cd api
composer install
```

2. 配置数据库连接（复制并修改配置文件）：
```bash
cp api/common/config/main-local.php.example api/common/config/main-local.php
```

3. 执行数据库迁移：
```bash
cd api
php yii migrate
```

4. 启动服务：
```bash
php yii serve --port=8801
```

### 前端配置

1. 安装依赖：
```bash
cd web
npm install
```

2. 启动开发服务器：
```bash
npm run dev
```

3. 构建生产版本：
```bash
npm run build
```

## 文档

详细功能说明请参阅 [数据标注系统介绍](docs/数据标注系统介绍-lite-v1.md)。

## demo

在线测试
http://label.kuaibiao.com.cn/
admin@kb.com.cn
sz123456

## 联系我
有需要部署,二次开发等技术支持的可联系我
QQ 87257302

## 许可证

MIT License