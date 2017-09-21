function parse(args, defaults) {
    const params = Object.assign({}, defaults);
    const paramPattern = /--([a-z-]+)=([^\s]*)/;
    for (let arg of args) {
        let matches = arg.match(paramPattern);
        if (matches !== null && params[matches[1]] !== undefined) {
            params[matches[1]] = matches[2];
        }
    }
    return params;
}

module.exports = parse;
