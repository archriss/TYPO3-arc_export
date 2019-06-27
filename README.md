# Archriss Export - Export creation tool #

## This extension able dev to prepare export in multiple format ##

- By default the export are in CSV format.
- Repository can be specified in configuration
- Admin account can see example configuration + code for creating export for all domain/model they want

## Basic example

> ext_typoscript_setup.txt
```
module.tx_arcexport.settings.Archriss\ArcExample\Domain\Model\Example {
    template = {$plugin.tx_example.view.templateRootPath}Export/Export.csv
    # name displayed in selectbox in the header of the module
    name = Example table
    # repository is optionnal
    repository = \Archriss\ArcExample\Domain\Repository\ExampleRepository
    # does the table / domain model contain exported checkbox field, if yes, specify the field name
    exportField = exported
    # exported content type (default is text/x-csv)
    contentType =
    # encoding (default is ISO-8859-1)
    charset =
    # exported file naming template (default is export-{datehour}.csv)
    filenameTmpl = example-{datehour}.csv
    # this dateformat will be used in filename generation and will replace {datehour} above
    dateHourFormat = %d%m%Y-%H%M
    # subfolder to export data to
    subfolder = Example
    # create pid-<pid> folder inside storage ?
    usePidSubFolder = 0
    # settings for specifique export (settings are for arc_export, localSettings are for this one)
    settings {
        # example :
        # in template use {localSettings.dateformat}
        dateformat = d/m/Y
    }
}
```

> Resources/Private/Templates/Export/Example.csv
```
{namespace arcExport=Archriss\ArcExport\ViewHelpers}
<f:layout name="Default" />
<f:section name="content"><arcExport:trim>
<arcExport:charsetConverter tocs="ISO-8859-1">
"<f:translate key="export.uid" extensionName="ArcExample" />";"<f:translate key="export.title" extensionName="ArcExample" />"
<f:for each="{rows}" as="row">"{row.uid}";"{row.title}";
</f:for>
</arcExport:charsetConverter>
</arcExport:trim></f:section>
```

> Classes/Domain/Model/Example.php
```
Class Example extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

    /**
     * Is exported
     * @var boolean
     */
    protected $exported = FALSE;

    public function getExported() {
        return $this->exported;
    }

    public function setExported($exported) {
        $this->exported = $exported;
    }

}
```

> Classes/Domain/Repository/ExampleRepository.php
```
class ExampleRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

    public function findByExported($pid, $exported = 0) {
        $query = $this->createQuery();
        $query->getQuerySettings()->setStoragePageIds((array) $pid);
        $query->matching($query->equals('exported', $exported));
        return $query->execute();
    }

    public function countByExported($pid, $exported = 0) {
        return $this->findByExported($pid, $exported)->count();
    }

}
```

> Configuration/TCA/tx_example_example.php
```
return array(
    ...
    'columns' => array(
        ...
        'exported' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:example/Resources/Private/Language/locallang_db.xlf:tx_example_example.exported',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('LLL:EXT:lang/locallang_common.xlf:yes', 1),
                ),
                'default' => '0',
            ),
        ),
        ...
    ),
    ...
);
```

## TODO

- Improve export declaration by replacing the namespace key usage by a custom key