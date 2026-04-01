const os = require('os');
const net = os.networkInterfaces();
let config = []
Object.values(net).forEach(item => {
    [].push.apply(config, item);
});
let Ip = '';
config.forEach(item => {
    if(item.family === 'IPv4' && !item.internal) {
        Ip = item.address;
    }
})
console.log(Ip)

exports.Ip = Ip;