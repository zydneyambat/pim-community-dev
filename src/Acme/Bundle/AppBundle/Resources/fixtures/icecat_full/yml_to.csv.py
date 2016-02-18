# coding: utf-8

"""
Transforming a yml file into a csv file
"""

import yaml
import csv

filename = "attribute_groups.yml"

def main():
    ""
    ""
    with open(filename, 'r') as streamin:
        with open('attribute_groups.csv', 'w') as streamout:
            fieldnames = ["code", "type", "label-de_DE", "label-en_US", "label-fr_FR"]
            writer = csv.DictWriter(streamout, fieldnames=fieldnames, delimiter=';', quotechar='"',)
            writer.writeheader()
            data=yaml.load(streamin)

            for group, dictValue in data['attribute_groups'].iteritems():
                row = {}
                row['code'] = dictValue['code']
                row['type'] = dictValue.get('type', 'RELATED')
                row['label-de_DE'] = dictValue.get('label-de_DE', '')
                row['label-en_US'] = dictValue.get('label-en_US', '')
                row['label-fr_FR'] = dictValue.get('label-fr_FR', '')
                writer.writerow(row)
    print("File successfully generated")


if __name__ == "__main__":
    main()
