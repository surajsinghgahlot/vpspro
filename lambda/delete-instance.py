import boto3

ec2_client = boto3.client('ec2')

def lambda_handler(event, context):
    value = event["name"]
    F1={"Name":"tag:Name", "Values":[value]}
    id_response=ec2_client.describe_instances(Filters=[F1])
    for i in id_response['Reservations']:
        for j in i['Instances']:
            id=(j['InstanceId'])
            ec2_client.terminate_instances(InstanceIds=[id])
            ec2_client.create_tags(Resources=[id], Tags=[{'Key':'Name', 'Value':'Deleted'}])