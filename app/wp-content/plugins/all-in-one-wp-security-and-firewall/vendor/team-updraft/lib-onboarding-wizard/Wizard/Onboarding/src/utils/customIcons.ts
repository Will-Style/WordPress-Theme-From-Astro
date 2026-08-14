import { type ComponentType } from 'react';
import right from './../assets/icons/right';
import left from './../assets/icons/left';
import database from './../assets/icons/database';
import compress from './../assets/icons/compress';
import cache from './../assets/icons/cache';
import minify from './../assets/icons/minify';
import magicWand from './../assets/icons/magicWand';
import license from './../assets/icons/license';
import settings from './../assets/icons/settings';
import plugin from './../assets/icons/plugin';
import loadingCircle from './../assets/icons/loadingCircle';
import continueArrowRight from './../assets/icons/continueArrowRight';
import key from './../assets/icons/key';
import firewall from './../assets/icons/firewall';
import userLock from './../assets/icons/userLock';
import userShield from './../assets/icons/userShield';
import bolt from './../assets/icons/bolt';
import linkArrow from './../assets/icons/linkArrow';
import minus from './../assets/icons/minus';
import plus from './../assets/icons/plus';
import backup from './../assets/icons/backup';
import migrate from './../assets/icons/migrate';
import restore from './../assets/icons/restore';
import cloudUpload from './../assets/icons/cloudUpload';

const customIcons: Record<string, ComponentType<any>> = {
    'arrow-right': right,
    'arrow-left': left,
    'database': database,
    'compress': compress,
    'cache': cache,
    'minify': minify,
    'magic-wand': magicWand,
    'license': license,
    'settings': settings,
    'plugin': plugin,
    'loading-circle': loadingCircle,
    'continue-arrow-right': continueArrowRight,
    'key': key,
    'firewall': firewall,
    'user-lock': userLock,
    'user-shield': userShield,
    'bolt': bolt,
    'link-arrow': linkArrow,
    'minus': minus,
    'plus': plus,
    'backup': backup,
    'migrate': migrate,
    'restore': restore,
    'cloud-upload': cloudUpload
};

export default customIcons;