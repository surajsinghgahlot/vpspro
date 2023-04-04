import boto3
import os

ec2_client = boto3.client('ec2')

def lambda_handler(event, context):
    ami = event["ami"]
    type = event["type"]
    name = event["name"]
    instance = ec2_client.run_instances(
        ImageId=ami,
        InstanceType=type,
        MaxCount=1,
        MinCount=1
    )
    instance_id = instance['Instances'][0]['InstanceId']
    ec2_client.create_tags(Resources=[instance_id], Tags=[{'Key':'Name', 'Value':name}])
    return "Instance create"