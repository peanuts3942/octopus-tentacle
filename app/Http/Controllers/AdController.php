<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdController extends Controller
{
    public function getVASTxml(Request $request): Response
    {
        if (! $request->has('videoUrl')) {
            abort(400, 'Missing videoUrl parameter');
        }

        $videoUrl = $request->input('videoUrl');
        $clickUrl = $request->input('clickUrl', '');
        $duration = $request->input('duration', '00:00:30');
        $skipOffset = $request->input('skipOffset', '00:00:10');
        $adTitle = $request->input('adTitle', 'Video Ad');
        $width = $request->input('width', '1920');
        $height = $request->input('height', '1080');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<VAST version="2.0">
    <Ad id="1">
        <InLine>
            <AdSystem>Tentacle</AdSystem>
            <AdTitle>{$adTitle}</AdTitle>
            <Description/>
            <Creatives>
                <Creative AdID="1" sequence="1">
                    <Linear skipoffset="{$skipOffset}">
                        <Duration>{$duration}</Duration>
                        <VideoClicks>
                            <ClickThrough><![CDATA[{$clickUrl}]]></ClickThrough>
                        </VideoClicks>
                        <MediaFiles>
                            <MediaFile id="1" delivery="progressive" type="video/mp4" bitrate="0" width="{$width}" height="{$height}">
                                <![CDATA[{$videoUrl}]]>
                            </MediaFile>
                        </MediaFiles>
                    </Linear>
                </Creative>
            </Creatives>
        </InLine>
    </Ad>
</VAST>
XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
