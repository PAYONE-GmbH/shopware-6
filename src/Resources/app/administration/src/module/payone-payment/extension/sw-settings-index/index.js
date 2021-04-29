import template from './sw-settings-index.html.twig';
import './sw-settings-index.scss';

const { Component } = Shopware;

const version = Shopware.Context.app.config.version;
const match = version.match(/((\d+)\.?(\d+?)\.?(\d+)?\.?(\d*))-?([A-z]+?\d+)?/i);

if(match && parseInt(match[2]) === 6 && parseInt(match[3]) < 4) {
    Component.override('sw-settings-index', {
        template
    });
}
