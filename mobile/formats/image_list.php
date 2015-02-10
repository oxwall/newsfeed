<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.newsfeed.mobile.format
 * @since 1.6.0
 */
class NEWSFEED_MFORMAT_ImageList extends NEWSFEED_FORMAT_ImageList
{
    const LIST_LIMIT = 8;

    public function getViewDir()
    {
        return $this->plugin->getMobileViewDir();
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->vars['blankImg'] = OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/1px.png';

        if ( !empty($this->vars['info']['route']) )
        {
            $this->vars['info']['url'] = $this->getUrl($this->vars['info']['route']);
        }

        $limit = self::LIST_LIMIT;

        // prepare view more url
        if ( !empty($this->vars['more']) )
        {
            $this->vars['more']['url'] = $this->getUrl($this->vars['more']);
            if ( !empty($this->vars['more']['limit']) )
            {
                $limit = $this->vars['more']['limit'];
            }
        }

        $this->list = array_slice($this->list, 0, $limit);
        $this->assign('list', $this->list);
        $this->assign('vars', $this->vars);

        $count = count($this->list);
        $this->assign('totalCount', $count);
        $count = $count > 4 ? 4 : $count;
        $this->assign('count', $count);
    }
}
