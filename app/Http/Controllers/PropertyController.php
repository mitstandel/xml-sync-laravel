<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyAgents;
use App\Models\PropertyFiles;
use App\Models\XMLMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PropertyController extends Controller
{
    public function sync()
    {
        $xmlMigrationObject = XMLMigrations::get(); //dd($xmlMigrations);

        $xmlMigrations = array();
        if ($xmlMigrationObject->count() > 0)
        {
            foreach ($xmlMigrationObject->toArray() as $row)
            {
                $xmlMigrations[] = $row['filename'];
            }
        }

        $filesInFolder = File::files('E:\xampp\htdocs\infinity\xml-files');     
        foreach ($filesInFolder as $path) {

            $filename = basename($path);

            if(in_array($filename, $xmlMigrations)) continue;

            $xmlString = file_get_contents($path);
            $xmlObject = simplexml_load_string($xmlString);

            foreach ($xmlObject as $type => $object)
            {
                $extraFields = array();
                foreach ($object->extraFields as $field)
                {
                    $key = (string) $field->attributes()->name;

                    if (isset($field->attributes()->value)) 
                    {
                        $extraFields[$key] = (string) $field->attributes()->value;
                    }
                }

                $modifyDate = $object->attributes()->modTime;

                $date = date_create_from_format('Y-m-d-H:i:s', $modifyDate);
                $modifyDate = date('Y-m-d H:i:s', $date->getTimestamp());

                $propertyData = array(
                    'type' => $type,
                    'last_modify_date' => $modifyDate,
                    'status' => (string) $object->attributes()->status,
                    'agent_code' => (string) $object->agentID,
                    'unique_code' => (string) $object->uniqueID,
                    'price' => (double) $object->price,
                    'area' => (double) $object->landDetails->area,
                    'frontage' => (double) $object->landDetails->frontage,
                    'address' => $extraFields['streetAddress'].', '.$object->address->suburb.', '.$object->address->state.' - '.$object->address->postcode,
                    'bedrooms' => (int) $object->features->bedrooms,
                    'bathrooms' => (int) $object->features->bathrooms,
                    'open_spaces' => (int) $object->features->openSpaces,
                    'headline' => (string) $object->headline,
                    'description' => (string) $object->description,
                    'latitude' => $extraFields['geoLat'],
                    'longitude' => $extraFields['geoLong']
                );

                $updateFields = array('type', 'last_modify_date', 'status', 'agent_code', 'price', 'area', 'frontage', 'address', 'bedrooms', 'bathrooms', 'open_spaces', 'headline', 'description', 'latitude', 'longitude');

                if (isset($object->landCategory))
                {
                    $propertyData['category'] = (string) $object->landCategory->attributes()->name;

                    array_push($updateFields, 'category');
                }

                if (isset($object->category))
                {
                    $propertyData['category'] = (string) $object->category->attributes()->name;

                    array_push($updateFields, 'category');
                }

                if (isset($object->soldDetails))
                {
                    $propertyData['sold_price'] = (string) $object->soldDetails->soldPrice;
                    $propertyData['sold_date'] = (string) $object->soldDetails->soldDate;

                    array_push($updateFields, 'sold_price');
                    array_push($updateFields, 'sold_date');
                }

                $property = Property::updateOrCreate(array('unique_code' => (string) $object->uniqueID), $propertyData);

                $this->saveObjects($object->objects->img, $property);
                $this->saveObjects($object->objects->floorplan, $property, 'floor-plan');

                $fileAttrs = $object->media->attachment->attributes();

                if (isset($fileAttrs->url))
                {
                    $fileData = array(
                        'property_id' => $property->id,
                        'file_id' => (string) $fileAttrs->id,
                        'type' => (string) $fileAttrs->usage,
                        'url' => (string) $fileAttrs->url,
                        'format' => (string) $fileAttrs->contentType
                    );

                    $updateFields = array('url', 'format');

                    PropertyFiles::upsert($fileData, $updateFields);
                }

                foreach ($object->listingAgent as $agent)
                {
                    $agentAttrs = $agent->attributes();

                    if (!isset($agent->name)) continue;

                    $agentData = array(
                        'property_id' => $property->id,
                        'agent_id' => (string) $agentAttrs->id,
                        'agent_name' => (string) $agent->name,
                        'agent_mobile' => (string) $agent->telephone[0],
                        'agent_bh' => (string) $agent->telephone[1],
                        'agent_email' => (string) $agent->email
                    );

                    $updateFields = array('agent_name', 'agent_mobile', 'agent_bh', 'agent_email');

                    PropertyAgents::upsert($agentData, $updateFields);
                }
            }

            $xmlData = array(
                'filename' => $filename,
                'run_date' => date('Y-m-d H:i:s')
            );

            XMLMigrations::create($xmlData);
        }
    }

    public function saveObjects($object, $property, $type = 'image')
    {
        foreach ($object as $files)
        {
            $fileAttrs = $files->attributes();

            if (!isset($fileAttrs->url)) continue;

            $modifyDate = $fileAttrs->modTime;

            $date = date_create_from_format('Y-m-d-H:i:s', $modifyDate);
            $modifyDate = date('Y-m-d H:i:s', $date->getTimestamp());

            $fileData = array(
                'property_id' => $property->id,
                'file_id' => (string) $fileAttrs->id,
                'record_id' => (string) $fileAttrs->recordId,
                'last_modify_date' => $modifyDate,
                'type' => $type,
                'title' => (string) $fileAttrs->title,
                'url' => (string) $fileAttrs->url,
                'format' => (string) $fileAttrs->format
            );

            $updateFields = array('record_id', 'last_modify_date', 'title', 'url', 'format');

            PropertyFiles::upsert($fileData, $updateFields);
        }
    }
}
