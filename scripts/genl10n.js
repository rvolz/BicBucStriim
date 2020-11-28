/* eslint-disable no-console */
/* Reads messages.yml and generates PHP files from it */
const read = require('read-yaml');
const fs = require('fs');

console.log(`converting ${process.argv[2]} ...`);
console.log(`storing results in dir ${process.argv[3]} ...`);

const messages = read.sync(process.argv[2]);
const targetDir = process.argv[3];
const langs = ['de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl','pl'];

data = [];
data.push("<?php");
data.push("# Generated file. Please don\'t edit here,");
data.push("# edit messages.yml instead. ");
data.push("#");

langs.forEach(lang => {
    data.push(`$lang${lang} = array(`);
    Object.entries(messages).forEach( msgArr => {
        const msg = msgArr[0];
        const locs = msgArr[1];
        if (locs[lang] !== undefined) {
            data.push(`\'${msg}\' => \'${locs[lang]}\',`);
        }
    });
    data.push(");\n");
});

fs.writeFileSync(`${targetDir}/langs.php`, data.join("\n"));
